import fs from "node:fs"
import os from "node:os"
import path from "node:path"
import { createServer } from "vite"
import { describe, expect, it, vi } from "vitest"
import jsLocalization, {
  changedLocales,
  entryModule,
  exportTranslations,
  isWatched,
  localeModule,
  UPDATE_EVENT,
} from "../../js/src/vite"

const lines = { dv: { Save: "ސޭވްކުރޭ" }, en: { Save: "Save" } }
const print = (data: unknown) => `node -e 'process.stdout.write(${JSON.stringify(JSON.stringify(data))})'`

function tempRoot(): string {
  return fs.mkdtempSync(path.join(os.tmpdir(), "js-localization-"))
}

describe("exportTranslations()", () => {
  it("parses what the command prints", () => {
    expect(exportTranslations(process.cwd(), print(lines))).toEqual(lines)
  })

  it("explains a failing command", () => {
    expect(() => exportTranslations(process.cwd(), "node -e 'console.error(\"No PHP\"); process.exit(1)'")).toThrow(
      /failed\.\nNo PHP/,
    )
  })

  it("explains output that isn't JSON", () => {
    expect(() => exportTranslations(process.cwd(), "echo hello")).toThrow(/didn't print JSON/)
  })

  it("rejects JSON that isn't an object of locales", () => {
    expect(() => exportTranslations(process.cwd(), print(["dv"]))).toThrow(/isn't an object of locales/)
  })
})

describe("the generated modules", () => {
  it("imports each locale lazily", () => {
    const code = entryModule(["dv", "en"])

    expect(code).toContain('"dv": () => import("virtual:js-localization/locale/dv")')
    expect(code).toContain('export const locales = ["dv","en"]')
    expect(code).toContain("export default loader")
  })

  it("parses lines from a JSON string", () => {
    const code = localeModule({ Save: "ސޭވްކުރޭ" })

    expect(code).toBe('export default JSON.parse("{\\"Save\\":\\"ސޭވްކުރޭ\\"}")\n')
  })
})

describe("changedLocales()", () => {
  it("lists changed, added and removed locales", () => {
    expect(changedLocales(lines, lines)).toEqual([])
    expect(changedLocales(lines, { dv: { Save: "ސޭވް" }, en: lines.en, ar: {} })).toEqual(["dv", "ar"])
    expect(changedLocales(lines, { dv: lines.dv })).toEqual(["en"])
  })
})

describe("isWatched()", () => {
  it("matches files in watched folders and watched files", () => {
    const watch = ["lang", "config/js-localization.php"]

    expect(isWatched("/app", watch, "/app/lang/dv/messages.php")).toBe(true)
    expect(isWatched("/app", watch, "/app/lang/dv.json")).toBe(true)
    expect(isWatched("/app", watch, "/app/config/js-localization.php")).toBe(true)
    expect(isWatched("/app", ["lang/"], "/app/lang/dv.json")).toBe(true)
    expect(isWatched("/app", watch, "/app/language/dv.json")).toBe(false)
    expect(isWatched("/app", watch, "/app/resources/js/app.ts")).toBe(false)
  })
})

describe("the plugin", () => {
  it("exports when a module asks before the build starts", async () => {
    const plugin = jsLocalization({ command: print(lines) })
    const load = plugin.load as (id: string) => string | null

    expect(load("\0virtual:js-localization")).toContain('export const locales = ["dv","en"]')
    expect(load("\0virtual:js-localization/locale/dv")).toContain("ސޭވްކުރޭ")
    expect(load("\0virtual:js-localization/locale/fr")).toBe('export default JSON.parse("{}")\n')
    expect(load("/app/resources/js/app.ts")).toBeNull()
    expect(plugin.translations()).toEqual(lines)
  })

  it("leaves other imports alone", () => {
    const resolveId = jsLocalization().resolveId as (source: string) => string | null

    expect(resolveId("virtual:js-localization")).toBe("\0virtual:js-localization")
    expect(resolveId("virtual:js-localization/locale/dv")).toBe("\0virtual:js-localization/locale/dv")
    expect(resolveId("@javaabu/i18n")).toBeNull()
  })

  it("runs the write command too", async () => {
    const root = tempRoot()
    const server = await createServer({
      root,
      configFile: false,
      logLevel: "silent",
      server: { middlewareMode: true, ws: false },
      plugins: [
        jsLocalization({ command: print(lines), write: 'node -e \'require("fs").writeFileSync("written", "1")\'' }),
      ],
    })

    await server.pluginContainer.buildStart({})
    await server.close()

    expect(fs.existsSync(path.join(root, "written"))).toBe(true)
  })

  it("reloads the page instead of sending lines when HMR is off, and logs failures", async () => {
    const root = tempRoot()
    const data = path.join(root, "lines.json")
    const file = path.join(root, "lang/dv.json")

    fs.mkdirSync(path.join(root, "lang"))
    fs.writeFileSync(data, JSON.stringify(lines))
    fs.writeFileSync(file, "{}")

    const server = await createServer({
      root,
      configFile: false,
      logLevel: "silent",
      server: { middlewareMode: true, ws: false },
      plugins: [
        jsLocalization({
          command: 'node -e \'process.stdout.write(require("fs").readFileSync("lines.json"))\'',
          hmr: false,
        }),
      ],
    })
    const send = vi.spyOn(server.ws, "send").mockImplementation(() => undefined)
    const error = vi.spyOn(server.config.logger, "error").mockImplementation(() => undefined)

    await server.pluginContainer.buildStart({})

    fs.writeFileSync(data, JSON.stringify({ ...lines, dv: { Save: "ސޭވް" } }))
    server.watcher.emit("change", file)

    await vi.waitFor(() => expect(send).toHaveBeenCalledWith({ type: "full-reload" }))
    expect(send).not.toHaveBeenCalledWith(expect.objectContaining({ event: UPDATE_EVENT }))

    fs.writeFileSync(data, "not json")
    server.watcher.emit("change", file)

    await vi.waitFor(() =>
      expect(error).toHaveBeenCalledWith(expect.stringContaining("didn't print JSON"), expect.anything()),
    )

    const handleHotUpdate = server.config.plugins.find((plugin) => plugin.name === "javaabu-js-localization")
      ?.handleHotUpdate as (context: { file: string }) => unknown

    expect(handleHotUpdate({ file })).toEqual([])
    expect(handleHotUpdate({ file: path.join(root, "app.ts") })).toBeUndefined()

    await server.close()
  })

  it("does nothing when the lines didn't change", async () => {
    const root = tempRoot()
    const file = path.join(root, "lang/dv.json")

    fs.mkdirSync(path.join(root, "lang"))
    fs.writeFileSync(file, "{}")

    const server = await createServer({
      root,
      configFile: false,
      logLevel: "silent",
      server: { middlewareMode: true, ws: false },
      plugins: [jsLocalization({ command: print(lines) })],
    })
    const send = vi.spyOn(server.ws, "send").mockImplementation(() => undefined)

    await server.pluginContainer.buildStart({})
    server.watcher.emit("change", file)

    await new Promise((resolve) => setTimeout(resolve, 200))

    expect(send).not.toHaveBeenCalled()

    await server.close()
  })
})
