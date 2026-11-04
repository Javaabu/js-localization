import { defineConfig } from "vitest/config"

export default defineConfig({
  test: {
    include: ["tests/js/**/*.test.ts"],
    testTimeout: 60_000,
    coverage: {
      provider: "v8",
      include: ["js/src/**/*.ts", "resources/js/**/*.ts"],
      exclude: ["**/*.d.ts"],
      reporter: ["text", "lcov", "json-summary"],
      thresholds: { lines: 90, statements: 90, functions: 90, branches: 85 },
    },
  },
})
