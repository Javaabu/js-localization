import fs from "node:fs"
import path from "node:path"
import { createLogger, createServer, type ViteDevServer } from "vite"
import { afterAll, afterEach, beforeAll, describe, expect, it, vi } from "vitest"
import jsLocalization from "../../../js/src/vite.js"
import { app, artisan, fixtureConfig, inFixtureApp, type Setup } from "./fixture"

const extra = path.join(app, "lang/dv/extra.php")
const newLocale = path.join(app, "lang/ar.json")

interface Sent {
  type: string
  event?: string
  data?: { locale: string; messages: Record<string, unknown> }
}

function waitFor(sent: Sent[], match: (message: Sent) => boolean): Promise<Sent> {
  return vi.waitFor(
    () => {
      const found = sent.find(match)

      if (!found) throw new Error("Not sent yet")

      return found
    },
    { timeout: 30_000, interval: 50 },
  )
}

describe.each<Setup>(["manifest", "standalone"])("the dev server (%s)", (setup) => {
  inFixtureApp(beforeAll, afterAll)

  let server: ViteDevServer
  const sent: Sent[] = []

  beforeAll(async () => {
    server = await createServer(fixtureConfig({ server: { middlewareMode: true, ws: false } }, {}, setup))
    await server.pluginContainer.buildStart({})

    vi.spyOn(server.ws, "send").mockImplementation(((message: Sent) => {
      sent.push(message)
    }) as never)
  })

  afterEach(async () => {
    const existed = fs.existsSync(extra) || fs.existsSync(newLocale)

    fs.rmSync(extra, { force: true })
    fs.rmSync(newLocale, { force: true })

    // Let the watcher see the removal before the next test.
    if (existed) await new Promise((resolve) => setTimeout(resolve, 500))

    sent.length = 0
  })

  afterAll(async () => {
    await server.close()
  })

  it("serves every locale through the virtual module", async () => {
    const module = await server.ssrLoadModule("virtual:js-localization")

    expect(module.locales).toEqual(["dv", "en"])
    expect((await module.loader("dv")).Save).toBe("ސޭވްކުރޭ")
    expect(await module.loader("fr")).toEqual({})
  })

  it("sends changed lines over HMR when a language file changes", async () => {
    fs.writeFileSync(extra, "<?php\n\nreturn ['hello' => 'ހެލޯ'];\n")
    server.watcher.emit("add", extra)

    const update = await waitFor(sent, (message) => message.event === "js-localization:update")

    expect(update.data?.locale).toBe("dv")
    expect(update.data?.messages.extra).toEqual({ hello: "ހެލޯ" })
    expect(sent.some((message) => message.type === "full-reload")).toBe(false)

    const module = await server.ssrLoadModule("virtual:js-localization")

    expect((await module.loader("dv")).extra).toEqual({ hello: "ހެލޯ" })
  })

  it("reloads the page when a locale is added", async () => {
    fs.writeFileSync(newLocale, '{"Save": "حفظ"}')
    server.watcher.emit("add", newLocale)

    await waitFor(sent, (message) => message.type === "full-reload")

    const module = await server.ssrLoadModule("virtual:js-localization")

    expect(module.locales).toEqual(["ar", "dv", "en"])
  })

  it("reloads the page when a locale is removed", async () => {
    fs.writeFileSync(newLocale, '{"Save": "حفظ"}')
    server.watcher.emit("add", newLocale)
    await waitFor(sent, (message) => message.type === "full-reload")
    sent.length = 0

    fs.rmSync(newLocale)
    server.watcher.emit("unlink", newLocale)

    await waitFor(sent, (message) => message.type === "full-reload")
    expect((await server.ssrLoadModule("virtual:js-localization")).locales).toEqual(["dv", "en"])
  })

  it("ignores other files", async () => {
    server.watcher.emit("change", path.join(app, "resources/js/app.ts"))

    await new Promise((resolve) => setTimeout(resolve, 300))

    expect(sent.filter((message) => message.event === "js-localization:update")).toEqual([])
  })
})

describe("an app that also adds jsLocalization() by hand", () => {
  inFixtureApp(beforeAll, afterAll)

  it("gets a warning from manifest that the plugin runs twice", async () => {
    const warnings: string[] = []
    const config = fixtureConfig({ server: { middlewareMode: true, ws: false } }, {}, "manifest")

    config.plugins?.push(jsLocalization({ command: `${artisan} lang:js --stdout` }))
    config.customLogger = {
      ...createLogger("warn"),
      warn: (message) => warnings.push(message),
      warnOnce: (message) => warnings.push(message),
    }

    const server = await createServer(config)

    await server.close()

    expect(warnings.join("\n")).toContain("javaabu-js-localization")
  })
})
