<?php

namespace Javaabu\JsLocalization;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Translation\Loader;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * Collects an app's translations through Laravel's own translation loader,
 * so JavaScript sees exactly the lines __() sees in PHP.
 */
class JsLocalization
{
    /** @var array<string, array<string, mixed>> */
    protected array $exported = [];

    protected ?string $fingerprint = null;

    public function __construct(
        protected Loader $loader,
        protected Filesystem $files,
        protected Config $config,
        protected CacheFactory $cache,
    ) {}

    /**
     * The locales to export: the configured ones, or every locale the app has
     * language files for, plus the app's locale and fallback locale.
     *
     * @return array<int, string>
     */
    public function locales(): array
    {
        $configured = $this->config->get('js-localization.locales');

        if (is_array($configured)) {
            return array_values(array_unique($configured));
        }

        $locales = [];

        foreach ($this->appPaths() as $path) {
            if (! $this->files->isDirectory($path)) {
                continue;
            }

            foreach ($this->files->directories($path) as $directory) {
                $locales[] = basename($directory);
            }

            foreach ($this->files->glob("{$path}/*.json") as $file) {
                $locales[] = basename($file, '.json');
            }
        }

        $locales[] = $this->config->get('app.locale');
        $locales[] = $this->config->get('app.fallback_locale');

        $locales = array_filter($locales, fn ($locale) => is_string($locale) && $this->isLocaleName($locale));

        $locales = array_values(array_unique($locales));

        sort($locales);

        return $locales;
    }

    /**
     * Whether a locale is one this app exports.
     */
    public function hasLocale(string $locale): bool
    {
        return in_array($locale, $this->locales(), true);
    }

    /**
     * One locale's lines in the shape @javaabu/i18n loads: JSON lines at the
     * top, then each group by its Laravel name ('messages', 'forum/thread',
     * 'shop::cart').
     *
     * @return array<string, mixed>
     */
    public function forLocale(string $locale): array
    {
        if (isset($this->exported[$locale])) {
            return $this->exported[$locale];
        }

        if (! $this->config->get('js-localization.cache.enabled', true)) {
            return $this->exported[$locale] = $this->collect($locale);
        }

        $key = 'js-localization:' . $locale . ':' . $this->fingerprint();

        return $this->exported[$locale] = $this->cacheStore()->rememberForever($key, fn () => $this->collect($locale));
    }

    /**
     * Every locale's lines, keyed by locale.
     *
     * @param  array<int, string>|null  $locales
     * @return array<string, array<string, mixed>>
     */
    public function all(?array $locales = null): array
    {
        $all = [];

        foreach ($locales ?? $this->locales() as $locale) {
            $all[$locale] = $this->forLocale($locale);
        }

        return $all;
    }

    /**
     * A hash of every language file and the export settings. It changes
     * whenever an exported line could have changed.
     */
    public function fingerprint(): string
    {
        if ($this->fingerprint) {
            return $this->fingerprint;
        }

        $parts = [json_encode([
            $this->config->get('js-localization.locales'),
            $this->config->get('js-localization.groups'),
            $this->config->get('js-localization.except'),
            $this->config->get('js-localization.json'),
            $this->config->get('js-localization.sort'),
            $this->config->get('app.locale'),
            $this->config->get('app.fallback_locale'),
        ])];

        foreach ($this->sourcePaths() as $path) {
            if (! $this->files->isDirectory($path)) {
                continue;
            }

            foreach ($this->files->allFiles($path) as $file) {
                $parts[] = $file->getPathname() . ':' . $file->getMTime() . ':' . $file->getSize();
            }
        }

        sort($parts);

        return $this->fingerprint = substr(hash('xxh128', implode("\n", $parts)), 0, 16);
    }

    /**
     * The endpoint's URL for a locale, or its template with {locale} in it,
     * as @javaabu/i18n's loader takes it. Relative to the host.
     */
    public function url(?string $locale = null): string
    {
        $prefix = trim((string) $this->config->get('js-localization.route.prefix', 'js-localization'), '/');
        $path = $prefix === '' ? '' : "/{$prefix}";

        return $path . '/' . ($locale ?? '{locale}') . '.json';
    }

    /**
     * Forget what this instance has collected, after language files change
     * in a long-running process.
     */
    public function flush(): void
    {
        $this->exported = [];
        $this->fingerprint = null;
    }

    /**
     * Every folder that holds language files: the app's, the JSON paths
     * packages register, and packages' namespaced folders.
     *
     * @return array<int, string>
     */
    public function sourcePaths(): array
    {
        return array_values(array_unique([
            ...$this->appPaths(),
            ...$this->jsonPaths(),
            ...array_values($this->loader->namespaces()),
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    protected function collect(string $locale): array
    {
        $lines = [];

        if ($this->config->get('js-localization.json', true)) {
            $lines = $this->loader->load($locale, '*', '*');
        }

        foreach ($this->groups($locale, exported_only: true) as $group) {
            $lines[$group] = $this->loadGroup($locale, $group);
        }

        $lines = array_filter($lines, fn ($value) => $value !== []);

        if ($this->config->get('js-localization.sort', true)) {
            $this->sortRecursively($lines);
        }

        return $lines;
    }

    /**
     * Every line a locale has, exported or not, flattened to the keys you
     * pass to __(): 'Save', 'messages.nested.title', 'shop::cart.empty'.
     *
     * @return array<string, mixed>
     */
    public function flatLines(string $locale, bool $namespaced = true): array
    {
        $flat = $this->loader->load($locale, '*', '*');

        foreach ($this->groups($locale) as $group) {
            if (! $namespaced && str_contains($group, '::')) {
                continue;
            }

            foreach ($this->flatten($this->loadGroup($locale, $group)) as $key => $line) {
                $flat["{$group}.{$key}"] = $line;
            }
        }

        return $flat;
    }

    /**
     * Whether a group ('messages', 'shop::cart') has a file in any locale.
     */
    public function isGroup(string $group): bool
    {
        foreach ($this->locales() as $locale) {
            if (in_array($group, $this->groups($locale), true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether lang:js and the endpoint export a group ('messages', 'shop::cart').
     */
    public function exports(string $group): bool
    {
        return $this->shouldExport($group);
    }

    /**
     * @return array<mixed>
     */
    public function loadGroup(string $locale, string $group): array
    {
        if (! str_contains($group, '::')) {
            return $this->loader->load($locale, $group, '*');
        }

        [$namespace, $name] = explode('::', $group, 2);

        return $this->loader->load($locale, $name, $namespace);
    }

    /**
     * The groups a locale has files for, optionally filtered by the groups
     * and except settings.
     *
     * @return array<int, string>
     */
    public function groups(string $locale, bool $exported_only = false): array
    {
        $groups = [];

        foreach ($this->appPaths() as $path) {
            array_push($groups, ...$this->groupsIn("{$path}/{$locale}"));
        }

        foreach ($this->loader->namespaces() as $namespace => $hint) {
            $names = $this->groupsIn("{$hint}/{$locale}");

            foreach ($this->appPaths() as $path) {
                array_push($names, ...$this->groupsIn("{$path}/vendor/{$namespace}/{$locale}"));
            }

            foreach ($names as $name) {
                $groups[] = "{$namespace}::{$name}";
            }
        }

        $groups = array_unique($groups);

        if ($exported_only) {
            $groups = array_filter($groups, fn (string $group) => $this->shouldExport($group));
        }

        return array_values($groups);
    }

    /**
     * @return array<int, string>
     */
    protected function groupsIn(string $directory): array
    {
        if (! $this->files->isDirectory($directory)) {
            return [];
        }

        $groups = [];

        foreach ($this->files->allFiles($directory) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $groups[] = str_replace('\\', '/', substr($file->getRelativePathname(), 0, -4));
        }

        return $groups;
    }

    protected function shouldExport(string $group): bool
    {
        $groups = (array) $this->config->get('js-localization.groups', ['*']);
        $except = (array) $this->config->get('js-localization.except', []);

        if (! Str::is($groups, $group)) {
            return false;
        }

        return ! Str::is($except, $group);
    }

    /**
     * The app's language folders, as the loader has them: lang/ and the
     * framework's own (validation, pagination, ...).
     *
     * @return array<int, string>
     */
    protected function appPaths(): array
    {
        if (method_exists($this->loader, 'paths')) {
            return array_values($this->loader->paths());
        }

        return [app()->langPath()];
    }

    /**
     * @return array<int, string>
     */
    protected function jsonPaths(): array
    {
        if (method_exists($this->loader, 'jsonPaths')) {
            return array_values($this->loader->jsonPaths());
        }

        return [];
    }

    protected function isLocaleName(string $name): bool
    {
        return $name !== 'vendor' && preg_match('/^[A-Za-z]{2,3}([_-][A-Za-z0-9]{2,8})*$/', $name) === 1;
    }

    /**
     * @param  array<mixed>  $lines
     * @return array<string, mixed>
     */
    protected function flatten(array $lines, string $prefix = ''): array
    {
        $flat = [];

        foreach ($lines as $key => $value) {
            if (is_array($value)) {
                $flat += $this->flatten($value, "{$prefix}{$key}.");

                continue;
            }

            $flat["{$prefix}{$key}"] = $value;
        }

        return $flat;
    }

    /**
     * @param  array<mixed>  $lines
     */
    protected function sortRecursively(array &$lines): void
    {
        ksort($lines, SORT_STRING);

        foreach ($lines as &$value) {
            if (is_array($value)) {
                $this->sortRecursively($value);
            }
        }
    }

    protected function cacheStore(): CacheRepository
    {
        return $this->cache->store($this->config->get('js-localization.cache.store'));
    }
}
