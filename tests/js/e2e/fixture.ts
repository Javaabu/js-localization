import path from "node:path"
import { fileURLToPath } from "node:url"
import laravel from "laravel-vite-plugin"
import { mergeConfig, type InlineConfig, type PluginOption } from "vite"
import manifest from "../../../vendor/javaabu/manifest/dist/vite.js"
import jsLocalization, { type JsLocalizationPluginOptions } from "../../../js/src/vite.js"

export const repo = fileURLToPath(new URL("../../..", import.meta.url))

export const app = path.join(repo, "tests/Fixtures/app")

export const artisan = `TESTBENCH_WORKING_PATH="${repo}" php "${path.join(repo, "vendor/bin/testbench")}"`

/**
 * How the app gets the plugin:
 *
 * - "manifest": as apps do, only manifest(), which runs the plugin the package
 *   registers (the built dist/vite.js);
 * - "standalone": jsLocalization() from source next to manifest(), with the
 *   registered one turned off, so these tests count towards coverage.
 */
export type Setup = "manifest" | "standalone"

function plugins(setup: Setup, options: JsLocalizationPluginOptions): PluginOption[] {
  const command = `${artisan} lang:js --stdout`
  const discover = `${artisan} manifest:discover`

  if (setup === "manifest") {
    return [manifest({ discover, plugins: { "js-localization": { command, ...options } } })]
  }

  return [manifest({ discover, plugins: { "js-localization": false } }), jsLocalization({ command, ...options })]
}

/**
 * The fixture app's vite.config.js. manifest finds package plugins by running
 * discovery from the directory Vite runs in, so run these with the fixture
 * app as the working directory: see inFixtureApp().
 */
export function fixtureConfig(
  overrides: InlineConfig = {},
  options: JsLocalizationPluginOptions = {},
  setup: Setup = "standalone",
): InlineConfig {
  return mergeConfig(
    {
      root: app,
      configFile: false,
      logLevel: "warn",
      plugins: [
        laravel({
          input: ["resources/js/app.ts"],
          buildDirectory: "e2e-build",
          hotFile: "public/e2e-hot",
          refresh: false,
        }),
        ...plugins(setup, options),
      ],
    },
    overrides,
  )
}

/** Runs the tests of a file from the fixture app, as Vite runs in an app. */
export function inFixtureApp(before: (fn: () => void) => void, after: (fn: () => void) => void): void {
  const cwd = process.cwd()

  before(() => process.chdir(app))
  after(() => process.chdir(cwd))
}
