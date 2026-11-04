import { execSync } from "node:child_process";
import fs from "node:fs";
import path from "node:path";
export const VIRTUAL_MODULE_ID = "virtual:js-localization";
export const UPDATE_EVENT = "js-localization:update";
const RESOLVED_PREFIX = "\0virtual:js-localization";
const LOCALE_PREFIX = `${VIRTUAL_MODULE_ID}/locale/`;
export const DEFAULT_COMMAND = "php artisan lang:js --stdout";
export const DEFAULT_WRITE_COMMAND = "php artisan lang:js";
export const DEFAULT_WATCH = ["lang", "config/js-localization.php"];
const DEBOUNCE_MS = 50;
/** Runs the export command and parses what it prints. */
export function exportTranslations(root, command) {
    let output;
    try {
        output = execSync(command, { cwd: root, stdio: "pipe", encoding: "utf8", maxBuffer: 256 * 1024 * 1024 });
    }
    catch (error) {
        const stderr = error.stderr?.trim();
        throw new Error(`[javaabu/js-localization] "${command}" failed.${stderr ? `\n${stderr}` : ""}`, { cause: error });
    }
    let data;
    try {
        data = JSON.parse(output);
    }
    catch (error) {
        throw new Error(`[javaabu/js-localization] "${command}" didn't print JSON. It should print what "php artisan lang:js --stdout" prints.\n${output.slice(0, 200)}`, { cause: error });
    }
    if (typeof data !== "object" || data === null || Array.isArray(data)) {
        throw new Error(`[javaabu/js-localization] "${command}" printed JSON that isn't an object of locales.`);
    }
    return data;
}
/** The module apps import: a lazy loader for @javaabu/i18n, one chunk per locale. */
export function entryModule(locales) {
    const loaders = locales
        .map((locale) => `  ${JSON.stringify(locale)}: () => import(${JSON.stringify(LOCALE_PREFIX + locale)}),`)
        .join("\n");
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
    ].join("\n");
}
export function localeModule(lines) {
    return `export default JSON.parse(${JSON.stringify(JSON.stringify(lines))})\n`;
}
/** The locales whose lines differ between two exports, including added and removed ones. */
export function changedLocales(before, after) {
    const locales = new Set([...Object.keys(before), ...Object.keys(after)]);
    return [...locales].filter((locale) => JSON.stringify(before[locale]) !== JSON.stringify(after[locale]));
}
export function isWatched(root, watch, file) {
    return [file, realpath(file)].some((candidate) => matchesWatch(root, watch, candidate));
}
function realpath(file) {
    try {
        return fs.realpathSync(file);
    }
    catch {
        // Deleted already: its folder still says where it was.
        try {
            return path.join(fs.realpathSync(path.dirname(file)), path.basename(file));
        }
        catch {
            return file;
        }
    }
}
function matchesWatch(root, watch, file) {
    const relative = path.relative(root, file).split(path.sep).join("/");
    return watch.some((entry) => {
        const normalized = entry.replace(/\/+$/, "");
        return relative === normalized || relative.startsWith(`${normalized}/`);
    });
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
export default function jsLocalization(options = {}) {
    const command = options.command ?? DEFAULT_COMMAND;
    const watch = options.watch ?? DEFAULT_WATCH;
    const write = options.write === true ? DEFAULT_WRITE_COMMAND : options.write || null;
    const hmr = options.hmr ?? true;
    let root = process.cwd();
    let config;
    let translations = {};
    let loaded = false;
    const refresh = () => {
        const previous = translations;
        translations = exportTranslations(root, command);
        loaded = true;
        if (write) {
            execSync(write, { cwd: root, stdio: "pipe" });
        }
        return previous;
    };
    const reload = (server, file) => {
        let previous;
        try {
            previous = refresh();
        }
        catch (error) {
            server.config.logger.error(error.message, { timestamp: true });
            return;
        }
        const changed = changedLocales(previous, translations);
        if (!changed.length) {
            return;
        }
        // A locale added or removed changes the entry module's list of chunks.
        const structural = Object.keys(translations).some((locale) => !(locale in previous)) ||
            Object.keys(previous).some((locale) => !(locale in translations));
        const graph = server.moduleGraph;
        for (const locale of changed) {
            const module = graph.getModuleById(RESOLVED_PREFIX + "/locale/" + locale);
            if (module)
                graph.invalidateModule(module);
        }
        if (structural) {
            const entry = graph.getModuleById(RESOLVED_PREFIX);
            if (entry)
                graph.invalidateModule(entry);
        }
        server.config.logger.info(`[javaabu/js-localization] ${path.relative(root, file)} changed: ${changed.join(", ")} updated.`, { timestamp: true });
        if (!hmr || structural) {
            server.ws.send({ type: "full-reload" });
            return;
        }
        for (const locale of changed) {
            server.ws.send({
                type: "custom",
                event: UPDATE_EVENT,
                data: { locale, messages: translations[locale] ?? {} },
            });
        }
    };
    return {
        name: "javaabu-js-localization",
        enforce: "pre",
        translations: () => translations,
        configResolved(resolved) {
            config = resolved;
            root = resolved.root;
        },
        buildStart() {
            // Every build exports afresh; the dev server only on start.
            if (config?.command === "build" || !loaded) {
                refresh();
            }
        },
        configureServer(server) {
            for (const entry of watch) {
                server.watcher.add(path.resolve(root, entry));
            }
            // Editors often write a file in several steps: export once they're done.
            let timer;
            const onChange = (file) => {
                if (!isWatched(root, watch, file))
                    return;
                clearTimeout(timer);
                timer = setTimeout(() => reload(server, file), DEBOUNCE_MS);
            };
            server.watcher.on("change", onChange);
            server.watcher.on("add", onChange);
            server.watcher.on("unlink", onChange);
        },
        handleHotUpdate(context) {
            // The plugin sends its own update for language files.
            if (isWatched(root, watch, context.file)) {
                return [];
            }
            return undefined;
        },
        resolveId(source) {
            if (source === VIRTUAL_MODULE_ID || source.startsWith(LOCALE_PREFIX)) {
                return "\0" + source;
            }
            return null;
        },
        load(id) {
            if (!id.startsWith(RESOLVED_PREFIX)) {
                return null;
            }
            if (!loaded) {
                refresh();
            }
            if (id === RESOLVED_PREFIX) {
                return entryModule(Object.keys(translations));
            }
            const locale = id.slice(("\0" + LOCALE_PREFIX).length);
            return localeModule(translations[locale] ?? {});
        },
    };
}
export { jsLocalization };
