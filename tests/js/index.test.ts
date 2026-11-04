// @vitest-environment jsdom
import { createTranslator } from "@javaabu/i18n"
import { describe, expect, it } from "vitest"
import {
  applyUpdate,
  DEFAULT_ENDPOINT,
  endpoint,
  listenForUpdates,
  UPDATE_EVENT,
  type TranslationsUpdate,
} from "../../resources/js/index"

function page(html: string): ParentNode {
  const root = document.createElement("div")

  root.innerHTML = html

  return root
}

describe("endpoint()", () => {
  it("defaults to the package's route", () => {
    expect(endpoint(page(""))).toBe(DEFAULT_ENDPOINT)
    expect(DEFAULT_ENDPOINT).toBe("/js-localization/{locale}.json")
  })

  it("reads the Blade entry's script tag", () => {
    expect(endpoint(page('<script data-js-localization="/script/{locale}.json"></script>'))).toBe(
      "/script/{locale}.json",
    )
  })

  it("lets a meta tag override the script tag", () => {
    const root = page(
      '<meta name="js-localization" content="/meta/{locale}.json"><script data-js-localization="/script/{locale}.json"></script>',
    )

    expect(endpoint(root)).toBe("/meta/{locale}.json")
  })
})

describe("applyUpdate()", () => {
  it("merges the lines into the translator", () => {
    const translator = createTranslator({ locale: "dv", messages: { dv: { Save: "ސޭވް" } } })

    applyUpdate({ locale: "dv", messages: { Save: "ސޭވްކުރޭ", Cancel: "ކެންސަލް" } }, translator)

    expect(translator.__("Save")).toBe("ސޭވްކުރޭ")
    expect(translator.__("Cancel")).toBe("ކެންސަލް")
  })
})

describe("listenForUpdates()", () => {
  it("applies updates sent over HMR", () => {
    const translator = createTranslator({ locale: "dv" })
    const listeners = new Map<string, (payload: TranslationsUpdate) => void>()

    listenForUpdates({ on: (event, callback) => listeners.set(event, callback) }, translator)
    listeners.get(UPDATE_EVENT)?.({ locale: "dv", messages: { Save: "ސޭވްކުރޭ" } })

    expect(translator.__("Save")).toBe("ސޭވްކުރޭ")
  })

  it("does nothing outside development", () => {
    expect(() => listenForUpdates(undefined)).not.toThrow()
  })
})
