// eslint-disable-next-line @typescript-eslint/triple-slash-reference -- apps type-check this file without our tsconfig
/// <reference path="./virtual.d.ts" />
/*
 * The app's translations, bundled by the Vite plugin, one chunk per locale:
 *
 *     import { configure } from "@javaabu/i18n"
 *     import { loader } from "@javaabu/js-localization/translations"
 *
 *     configure({ loader })
 *
 * In development, lines change on the page as you edit lang/.
 */
import { listenForUpdates } from "./index"

export { default, loader, locales } from "virtual:js-localization"

listenForUpdates(import.meta.hot)
