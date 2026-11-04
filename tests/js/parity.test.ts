import { execFileSync } from "node:child_process"
import { readFileSync } from "node:fs"
import { createTranslator } from "@javaabu/i18n"
import { describe, expect, it } from "vitest"

interface Case {
  key: string
  locale: string
  expected: string
  replace?: Record<string, string>
  count?: number
}

const cases = JSON.parse(readFileSync("tests/Fixtures/parity.json", "utf8")) as Case[]

describe("lang:js output in @javaabu/i18n", () => {
  const exported = execFileSync("vendor/bin/testbench", ["lang:js", "--stdout"], { encoding: "utf8" })

  it.each([
    ["the whole export", (locale: string) => createTranslator({ locale, messages: JSON.parse(exported) })],
    [
      "one locale at a time",
      (locale: string) => {
        const all = JSON.parse(exported) as Record<string, object>
        const translator = createTranslator({ locale })

        for (const [code, lines] of Object.entries(all)) translator.load(lines, { locale: code })

        return translator
      },
    ],
  ])("translates like Laravel from %s", (_, make) => {
    for (const test of cases) {
      const translator = make(test.locale)
      const actual =
        test.count === undefined
          ? translator.__(test.key, test.replace)
          : translator.trans_choice(test.key, test.count, test.replace)

      expect(actual, `${test.locale}: ${test.key}`).toBe(test.expected)
    }
  })
})
