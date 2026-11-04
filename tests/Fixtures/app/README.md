# Fixture app

The Laravel app the tests run in (Testbench's base path). It has English and Dhivehi language files in `lang/`, and a package in `vendor/acme/shop` that registers its own PHP and JSON translations.

Run artisan in it with `vendor/bin/testbench`, for example `vendor/bin/testbench lang:js --stdout`.
