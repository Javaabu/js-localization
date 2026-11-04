import path from "node:path"
import { fileURLToPath } from "node:url"
import laravel from "laravel-vite-plugin"
import { mergeConfig, type InlineConfig } from "vite"
import manifest from "../../../vendor/javaabu/manifest/dist/vite.js"
import jsLocalization, { type JsLocalizationPluginOptions } from "../../../js/src/vite.js"

export const repo = fileURLToPath(new URL("../../..", import.meta.url))

export const app = path.join(repo, "tests/Fixtures/app")

export const artisan = `TESTBENCH_WORKING_PATH="${repo}" php "${path.join(repo, "vendor/bin/testbench")}"`

/** The fixture app's vite.config.js, with the plugin from source so it counts towards coverage. */
export function fixtureConfig(overrides: InlineConfig = {}, options: JsLocalizationPluginOptions = {}): InlineConfig {
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
        manifest({ discover: `${artisan} manifest:discover` }),
        jsLocalization({ command: `${artisan} lang:js --stdout`, ...options }),
      ],
    },
    overrides,
  )
}
