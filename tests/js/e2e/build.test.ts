import fs from "node:fs"
import path from "node:path"
import { build } from "vite"
import { afterAll, beforeAll, describe, expect, it } from "vitest"
import { app, fixtureConfig, inFixtureApp, type Setup } from "./fixture"

const out = path.join(app, "public/e2e-build")

function readChunks(): string[] {
  const dir = path.join(out, "assets")

  return fs.readdirSync(dir).map((file) => fs.readFileSync(path.join(dir, file), "utf8"))
}

function readManifest(): Record<string, { file: string; isEntry?: boolean; dynamicImports?: string[] }> {
  return JSON.parse(fs.readFileSync(path.join(out, "manifest.json"), "utf8"))
}

describe.each<Setup>(["manifest", "standalone"])("vite build (%s)", (setup) => {
  inFixtureApp(beforeAll, afterAll)

  const plugins: string[] = []

  beforeAll(async () => {
    fs.rmSync(out, { recursive: true, force: true })

    const config = fixtureConfig({}, {}, setup)

    config.plugins?.push({
      name: "list-plugins",
      configResolved(resolved) {
        plugins.push(...resolved.plugins.map((plugin) => plugin.name))
      },
    })

    await build(config)
  })

  it("runs the plugin once", () => {
    expect(plugins.filter((name) => name === "javaabu-js-localization")).toHaveLength(1)
  })

  it("bundles each locale into its own chunk", () => {
    const chunks = readChunks()
    const dv = chunks.filter((code) => code.includes("ސޭވްކުރޭ"))
    const en = chunks.filter((code) => code.includes("These credentials do not match our records."))

    expect(dv).toHaveLength(1)
    expect(en).toHaveLength(1)
    expect(dv[0]).not.toContain("These credentials do not match our records.")
  })

  it("loads the locales lazily from the app's entry", () => {
    const entry = readManifest()["resources/js/app.ts"]

    expect(entry?.isEntry).toBe(true)
    expect(entry?.dynamicImports).toHaveLength(2)
    expect(fs.readFileSync(path.join(out, entry?.file as string), "utf8")).not.toContain("ސޭވްކުރޭ")
  })

  it("builds the Blade entry too", () => {
    expect(Object.keys(readManifest()).some((key) => key.endsWith("resources/js/blade.ts"))).toBe(true)
  })
})
