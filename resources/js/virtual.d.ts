/** Served by the Vite plugin (vendor/javaabu/js-localization/dist/vite.js). */
declare module "virtual:js-localization" {
  /** Every exported locale. */
  export const locales: string[]

  /** Loads one locale's lines, as @javaabu/i18n's `loader` option takes it. */
  export function loader(locale: string): Promise<Record<string, unknown>>

  export default loader
}
