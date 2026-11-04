// @vitest-environment jsdom
import type {} from "../../resources/js/blade"
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest"

const lines: Record<string, unknown> = {
  "/js-localization/dv.json": { Save: "ސޭވްކުރޭ", messages: { welcome: ":app އަށް މަރުޙަބާ" } },
  "/js-localization/en.json": { Save: "Save", messages: { welcome: "Welcome to :app" } },
  "/i18n/dv.json": { Save: "ސޭވް" },
  "/i18n/en.json": {},
}

function respond(url: string): Promise<Response> {
  const body = lines[url]

  return Promise.resolve(
    body ? new Response(JSON.stringify(body), { status: 200 }) : new Response("Not found", { status: 404 }),
  )
}

describe("the Blade entry", () => {
  beforeEach(() => {
    vi.resetModules()
    vi.stubGlobal(
      "fetch",
      vi.fn((url: string) => respond(String(url))),
    )
    document.documentElement.lang = "dv"
    document.head.innerHTML = ""
    document.body.innerHTML = ""
    delete (globalThis as { I18n?: unknown }).I18n
  })

  afterEach(() => {
    vi.unstubAllGlobals()
  })

  it("puts the helpers on window and loads the page's locale from the endpoint", async () => {
    await import("../../resources/js/blade")

    await globalThis.I18n.ready()

    expect(fetch).toHaveBeenCalledWith("/js-localization/dv.json", expect.anything())
    expect(globalThis.__("Save")).toBe("ސޭވްކުރޭ")
    expect(globalThis.trans("messages.welcome", { app: "ޖާވާބު" })).toBe("ޖާވާބު އަށް މަރުޙަބާ")
    expect(globalThis.I18n.getLocale()).toBe("dv")
  })

  it("uses the endpoint on its script tag", async () => {
    document.body.innerHTML = '<script type="module" data-js-localization="/i18n/{locale}.json"></script>'

    await import("../../resources/js/blade")
    await globalThis.I18n.ready()

    expect(fetch).toHaveBeenCalledWith("/i18n/dv.json", expect.anything())
    expect(globalThis.__("Save")).toBe("ސޭވް")
  })

  it("fetches another locale when the page switches", async () => {
    await import("../../resources/js/blade")

    globalThis.I18n.setLocale("en")
    await globalThis.I18n.ready()

    expect(globalThis.__("Save")).toBe("Save")
  })
})
