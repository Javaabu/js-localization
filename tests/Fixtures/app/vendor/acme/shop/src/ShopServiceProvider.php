<?php

namespace Acme\Shop;

use Illuminate\Support\ServiceProvider;

class ShopServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'shop');
        $this->loadJsonTranslationsFrom(__DIR__ . '/../lang');
    }
}
