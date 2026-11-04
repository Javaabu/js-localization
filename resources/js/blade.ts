/*
 * The Blade entry (js-localization::i18n). It puts __(), trans() and
 * trans_choice() on window, and the rest of @javaabu/i18n on window.I18n,
 * then loads the page's locale from the endpoint.
 */
import * as I18n from "@javaabu/i18n"
import "@javaabu/i18n/global"
import { listenForUpdates, useEndpoint } from "./index"

// @javaabu/i18n/global's types don't declare these yet (Javaabu/i18n#2).
declare global {
  var I18n: typeof import("@javaabu/i18n")
  var __: typeof I18n.__
  var trans: typeof I18n.trans
  var trans_choice: typeof I18n.trans_choice
}

globalThis.I18n ??= I18n

useEndpoint()

listenForUpdates(import.meta.hot)
