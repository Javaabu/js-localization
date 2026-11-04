import fs from "node:fs"
import path from "node:path"
import { build } from "vite"
import { beforeAll, describe, expect, it } from "vitest"
import { app, fixtureConfig } from "./fixture"

const out = path.join(app, "public/e2e-build")

function readChunks(): Map<string, string> {
  const dir = path.join(out, "assets")

  return new Map(fs.readdirSync(dir).map((file) => [file, fs.readFileSync(path.join(dir, file), "utf8")]))
}

describe("vite build", () => {
  beforeAll(async () => {
    fs.rmSync(out, { recursive: true, force: true })

    await build(fixtureConfig())
  })

  it("bundles each locale into its own chunk", () => {
    const chunks = [...readChunks().values()]
    const dv = chunks.filter((code) => code.includes("ސޭވްކުރޭ"))
    const en = chunks.filter((code) => code.includes("These credentials do not match our records."))

    expect(dv).toHaveLength(1)
    expect(en).toHaveLength(1)
    expect(dv[0]).not.toContain("These credentials do not match our records.")
  })

  it("loads the locales lazily from the app's entry", () => {
    const manifest = JSON.parse(fs.readFileSync(path.join(out, "manifest.json"), "utf8"))
    const entry = manifest["resources/js/app.ts"]

    expect(entry.isEntry).toBe(true)
    expect(entry.dynamicImports).toHaveLength(2)

    const code = fs.readFileSync(path.join(out, entry.file), "utf8")

    expect(code).not.toContain("ސޭވްކުރޭ")
  })

  it("builds the Blade entry too", () => {
    const manifest = JSON.parse(fs.readFileSync(path.join(out, "manifest.json"), "utf8"))
    const blade = Object.keys(manifest).find((key) => key.endsWith("resources/js/blade.ts"))

    expect(blade).toBeDefined()
  })
})
