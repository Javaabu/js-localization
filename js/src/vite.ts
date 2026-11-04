import { execSync } from "node:child_process"
import fs from "node:fs"
import path from "node:path"
import type { Plugin, ResolvedConfig, ViteDevServer } from "vite"

export interface JsLocalizationPluginOptions {
  /**
   * The command that prints every locale as JSON, run in the Vite root. Use
   * `"./vendor/bin/sail artisan lang:js --stdout"` or a `docker compose exec`
   * command when PHP runs in a container.
   * @default "php artisan lang:js --stdout"
   */
  command?: string

  /**
   * Files and folders, relative to the Vite root, whose changes re-run the
   * command in development.
   * @default ["lang", "config/js-localization.php"]
   */
  watch?: string[]

  /**
   * Also run `php artisan lang:js` (or this command) when Vite starts and
   * whenever the lines change, so the files at `js-localization.path` stay
   * current for code that imports them directly.
   * @default false
   */
  write?: boolean | string

  /**
   * Send changed lines to the page in development, so `__()` updates without
   * a reload. Turn it off to let the page reload instead.
   * @default true
   */
  hmr?: boolean
}

export interface JsLocalizationPlugin extends Plugin {
  /** The lines of every locale, as the command last printed them. */
  translations(): Translations
}

export type Translations = Record<string, Record<string, unknown>>

export const VIRTUAL_MODULE_ID = "virtual:js-localization"
export const UPDATE_EVENT = "js-localization:update"

const RESOLVED_PREFIX = "\0virtual:js-localization"
const LOCALE_PREFIX = `${VIRTUAL_MODULE_ID}/locale/`

export const DEFAULT_COMMAND = "php artisan lang:js --stdout"
export const DEFAULT_WRITE_COMMAND = "php artisan lang:js"
export const DEFAULT_WATCH = ["lang", "config/js-localization.php"]

const DEBOUNCE_MS = 50

/** Runs the export command and parses what it prints. */
export function exportTranslations(root: string, command: string): Translations {
  let output: string

  try {
    output = execSync(command, { cwd: root, stdio: "pipe", encoding: "utf8", maxBuffer: 256 * 1024 * 1024 })
  } catch (error) {
    const stderr = (error as { stderr?: string }).stderr?.trim()

    throw new Error(`[javaabu/js-localization] "${command}" failed.${stderr ? `\n${stderr}` : ""}`, { cause: error })
  }

  let data: unknown

  try {
    data = JSON.parse(output)
  } catch (error) {
    throw new Error(
      `[javaabu/js-localization] "${command}" didn't print JSON. It should print what "php artisan lang:js --stdout" prints.\n${output.slice(0, 200)}`,
      { cause: error },
    )
  }

  if (typeof data !== "object" || data === null || Array.isArray(data)) {
    throw new Error(`[javaabu/js-localization] "${command}" printed JSON that isn't an object of locales.`)
  }

  return data as Translations
}

/** The module apps import: a lazy loader for @javaabu/i18n, one chunk per locale. */
export function entryModule(locales: string[]): string {
  const loaders = locales
    .map((locale) => `  ${JSON.stringify(locale)}: () => import(${JSON.stringify(LOCALE_PREFIX + locale)}),`)
    .join("\n")

  return [
    `const loaders = {\n${loaders}\n}`,
    "",
    `export const locales = ${JSON.stringify(locales)}`,
    "",
    "export function loader(locale) {",
    "  const load = Object.prototype.hasOwnProperty.call(loaders, locale) ? loaders[locale] : undefined",
    "",
    "  return load ? load().then((module) => module.default) : Promise.resolve({})",
    "}",
    "",
    "export default loader",
    "",
  ].join("\n")
}

export function localeModule(lines: Record<string, unknown>): string {
  return `export default JSON.parse(${JSON.stringify(JSON.stringify(lines))})\n`
}

/** The locales whose lines differ between two exports, including added and removed ones. */
export function changedLocales(before: Translations, after: Translations): string[] {
  const locales = new Set([...Object.keys(before), ...Object.keys(after)])

  return [...locales].filter((locale) => JSON.stringify(before[locale]) !== JSON.stringify(after[locale]))
}

export function isWatched(root: string, watch: string[], file: string): boolean {
  return [file, realpath(file)].some((candidate) => matchesWatch(root, watch, candidate))
}

function realpath(file: string): string {
  try {
    return fs.realpathSync(file)
  } catch {
    // Deleted already: its folder still says where it was.
    try {
      return path.join(fs.realpathSync(path.dirname(file)), path.basename(file))
    } catch {
      return file
    }
  }
}

function matchesWatch(root: string, watch: string[], file: string): boolean {
  const relative = path.relative(root, file).split(path.sep).join("/")

  return watch.some((entry) => {
    const normalized = entry.replace(/\/+$/, "")

    return relative === normalized || relative.startsWith(`${normalized}/`)
  })
}

/**
 * Serves the app's translations to its bundle and keeps them current:
 *
 *     plugins: [laravel({ input: ["resources/js/app.ts"] }), manifest(), jsLocalization()]
 *
 * then, in the app:
 *
 *     import { configure } from "@javaabu/i18n"
 *     import { loader } from "@javaabu/js-localization/translations"
 *
 *     configure({ loader })
 */
export default function jsLocalization(options: JsLocalizationPluginOptions = {}): JsLocalizationPlugin {
  const command = options.command ?? DEFAULT_COMMAND
  const watch = options.watch ?? DEFAULT_WATCH
  const write = options.write === true ? DEFAULT_WRITE_COMMAND : options.write || null
  const hmr = options.hmr ?? true

  let root = process.cwd()
  let config: ResolvedConfig | undefined
  let translations: Translations = {}
  let loaded = false

  const refresh = (): Translations => {
    const previous = translations

    translations = exportTranslations(root, command)
    loaded = true

    if (write) {
      execSync(write, { cwd: root, stdio: "pipe" })
    }

    return previous
  }

  const reload = (server: ViteDevServer, file: string): void => {
    let previous: Translations

    try {
      previous = refresh()
    } catch (error) {
      server.config.logger.error((error as Error).message, { timestamp: true })

      return
    }

    const changed = changedLocales(previous, translations)

    if (!changed.length) {
      return
    }

    // A locale added or removed changes the entry module's list of chunks.
    const structural =
      Object.keys(translations).some((locale) => !(locale in previous)) ||
      Object.keys(previous).some((locale) => !(locale in translations))
    const graph = server.moduleGraph

    for (const locale of changed) {
      const module = graph.getModuleById(RESOLVED_PREFIX + "/locale/" + locale)

      if (module) graph.invalidateModule(module)
    }

    if (structural) {
      const entry = graph.getModuleById(RESOLVED_PREFIX)

      if (entry) graph.invalidateModule(entry)
    }

    server.config.logger.info(
      `[javaabu/js-localization] ${path.relative(root, file)} changed: ${changed.join(", ")} updated.`,
      { timestamp: true },
    )

    if (!hmr || structural) {
      server.ws.send({ type: "full-reload" })

      return
    }

    for (const locale of changed) {
      server.ws.send({
        type: "custom",
        event: UPDATE_EVENT,
        data: { locale, messages: translations[locale] ?? {} },
      })
    }
  }

  return {
    name: "javaabu-js-localization",
    enforce: "pre",

    translations: () => translations,

    configResolved(resolved) {
      config = resolved
      root = resolved.root
    },

    buildStart() {
      // Every build exports afresh; the dev server only on start.
      if (config?.command === "build" || !loaded) {
        refresh()
      }
    },

    configureServer(server) {
      for (const entry of watch) {
        server.watcher.add(path.resolve(root, entry))
      }

      // Editors often write a file in several steps: export once they're done.
      let timer: ReturnType<typeof setTimeout> | undefined

      const onChange = (file: string) => {
        if (!isWatched(root, watch, file)) return

        clearTimeout(timer)
        timer = setTimeout(() => reload(server, file), DEBOUNCE_MS)
      }

      server.watcher.on("change", onChange)
      server.watcher.on("add", onChange)
      server.watcher.on("unlink", onChange)
    },

    handleHotUpdate(context) {
      // The plugin sends its own update for language files.
      if (isWatched(root, watch, context.file)) {
        return []
      }

      return undefined
    },

    resolveId(source) {
      if (source === VIRTUAL_MODULE_ID || source.startsWith(LOCALE_PREFIX)) {
        return "\0" + source
      }

      return null
    },

    load(id) {
      if (!id.startsWith(RESOLVED_PREFIX)) {
        return null
      }

      if (!loaded) {
        refresh()
      }

      if (id === RESOLVED_PREFIX) {
        return entryModule(Object.keys(translations))
      }

      const locale = id.slice(("\0" + LOCALE_PREFIX).length)

      return localeModule(translations[locale] ?? {})
    },
  }
}

export { jsLocalization }
