import type { Plugin } from "vite";
export interface JsLocalizationPluginOptions {
    /**
     * The command that prints every locale as JSON, run in the Vite root. Use
     * `"./vendor/bin/sail artisan lang:js --stdout"` or a `docker compose exec`
     * command when PHP runs in a container.
     * @default "php artisan lang:js --stdout"
     */
    command?: string;
    /**
     * Files and folders, relative to the Vite root, whose changes re-run the
     * command in development.
     * @default ["lang", "config/js-localization.php"]
     */
    watch?: string[];
    /**
     * Also run `php artisan lang:js` (or this command) when Vite starts and
     * whenever the lines change, so the files at `js-localization.path` stay
     * current for code that imports them directly.
     * @default false
     */
    write?: boolean | string;
    /**
     * Send changed lines to the page in development, so `__()` updates without
     * a reload. Turn it off to let the page reload instead.
     * @default true
     */
    hmr?: boolean;
}
export interface JsLocalizationPlugin extends Plugin {
    /** The lines of every locale, as the command last printed them. */
    translations(): Translations;
}
export type Translations = Record<string, Record<string, unknown>>;
export declare const VIRTUAL_MODULE_ID = "virtual:js-localization";
export declare const UPDATE_EVENT = "js-localization:update";
export declare const DEFAULT_COMMAND = "php artisan lang:js --stdout";
export declare const DEFAULT_WRITE_COMMAND = "php artisan lang:js";
export declare const DEFAULT_WATCH: string[];
/** Runs the export command and parses what it prints. */
export declare function exportTranslations(root: string, command: string): Translations;
/** The module apps import: a lazy loader for @javaabu/i18n, one chunk per locale. */
export declare function entryModule(locales: string[]): string;
export declare function localeModule(lines: Record<string, unknown>): string;
/** The locales whose lines differ between two exports, including added and removed ones. */
export declare function changedLocales(before: Translations, after: Translations): string[];
export declare function isWatched(root: string, watch: string[], file: string): boolean;
/**
 * Serves the app's translations to its bundle and keeps them current. The
 * package registers it with javaabu/manifest, so `manifest()` runs it:
 *
 *     plugins: [laravel({ input: ["resources/js/app.ts"] }), manifest()]
 *
 * Adding it by hand still works, with the registered one turned off:
 *
 *     manifest({ plugins: { "js-localization": false } }), jsLocalization({ ... })
 *
 * then, in the app:
 *
 *     import { configure } from "@javaabu/i18n"
 *     import { loader } from "@javaabu/js-localization/translations"
 *
 *     configure({ loader })
 */
export default function jsLocalization(options?: JsLocalizationPluginOptions): JsLocalizationPlugin;
export { jsLocalization };
