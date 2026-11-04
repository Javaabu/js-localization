import { configure, translator, type Messages } from "@javaabu/i18n"

/** The endpoint javaabu/js-localization registers, as @javaabu/i18n's loader takes it. */
export const DEFAULT_ENDPOINT = "/js-localization/{locale}.json"

/** The data attribute the Blade entry's script tag carries the endpoint in. */
export const ENDPOINT_ATTRIBUTE = "data-js-localization"

/** What the Vite plugin sends when a language file changes in development. */
export interface TranslationsUpdate {
  locale: string
  messages: Messages
}

export const UPDATE_EVENT = "js-localization:update"

/**
 * The endpoint for this page: `<meta name="js-localization">` when the page
 * sets one, then the one on the Blade entry's script tag (from the
 * `route.prefix` config), then the default.
 */
export function endpoint(root: ParentNode = document): string {
  const script = root.querySelector(`script[${ENDPOINT_ATTRIBUTE}]`)
  const meta = root.querySelector('meta[name="js-localization"]')

  return meta?.getAttribute("content") || script?.getAttribute(ENDPOINT_ATTRIBUTE) || DEFAULT_ENDPOINT
}

/**
 * Loads the app's translations from the endpoint, for the page's locale and
 * for any locale `setLocale()` switches to.
 */
export function useEndpoint(url: string = endpoint()): void {
  configure({ loader: url })
}

/** Merges an update from the Vite plugin into the shared translator. */
export function applyUpdate(update: TranslationsUpdate, target = translator): void {
  target.load(update.messages, { locale: update.locale })
}

/** The part of Vite's `import.meta.hot` that listenForUpdates() needs. */
export interface HotContext {
  on(event: string, callback: (payload: TranslationsUpdate) => void): void
}

/**
 * In development, applies the lines the Vite plugin sends when a language
 * file changes, so the page updates without a reload:
 * `listenForUpdates(import.meta.hot)`.
 */
export function listenForUpdates(hot: HotContext | undefined, target = translator): void {
  hot?.on(UPDATE_EVENT, (update) => applyUpdate(update, target))
}
