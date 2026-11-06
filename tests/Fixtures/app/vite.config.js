// The fixture app's Vite config, as an app would write it: manifest() also
// runs js-localization's Vite plugin. The only difference: artisan runs
// through Testbench, since the fixture app has no artisan file of its own.
import path from "node:path"
import { fileURLToPath } from "node:url"
import laravel from "laravel-vite-plugin"
import { defineConfig } from "vite"
import manifest from "../../../vendor/javaabu/manifest/dist/vite.js"

const repo = fileURLToPath(new URL("../../..", import.meta.url))
const artisan = `TESTBENCH_WORKING_PATH="${repo}" php "${path.join(repo, "vendor/bin/testbench")}"`

export default defineConfig({
  plugins: [
    laravel({ input: ["resources/js/app.ts"], buildDirectory: "e2e-build", hotFile: "public/e2e-hot", refresh: false }),
    manifest({
      discover: `${artisan} manifest:discover`,
      plugins: { "js-localization": { command: `${artisan} lang:js --stdout` } },
    }),
  ],
})
