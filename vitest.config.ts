import { defineConfig } from "vitest/config"

export default defineConfig({
  test: {
    include: ["tests/js/**/*.test.ts"],
    testTimeout: 60_000,
    // The e2e tests change the fixture app's lang/ folder while others build it.
    fileParallelism: false,
    coverage: {
      provider: "v8",
      include: ["js/src/**/*.ts", "resources/js/**/*.ts"],
      exclude: ["**/*.d.ts"],
      reporter: ["text", "lcov", "json-summary"],
      thresholds: { lines: 90, statements: 90, functions: 90, branches: 85 },
    },
  },
})
