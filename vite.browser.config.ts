import { defineConfig } from "vite"
import { packageBuild } from "./vendor/javaabu/manifest/dist/package.js"

// The prebuilt Blade entry, with @javaabu/i18n bundled, for apps that don't
// build packages' assets with Vite. Registered with ->dist('../dist/browser').
export default defineConfig(packageBuild({ input: ["js/blade.ts"], outDir: "dist/browser" }))
