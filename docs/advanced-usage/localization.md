---
title: Localization
sidebar_position: 4
---

The package's own text, the output of `lang:js`, `lang:missing` and `lang:translate`, is translated into English and Dhivehi. Commands speak the app's locale:

```php
app()->setLocale('dv');
```

To change the wording, publish the translations and edit them:

```bash
php artisan vendor:publish --tag=js-localization-translations
```

They go to `lang/vendor/js-localization/{en,dv}/commands.php`. Command descriptions (in `php artisan list`) and exception messages stay in English.

# Terms to review

The Dhivehi was written without a native speaker's review. These terms are the least certain; corrections are welcome:

| English | Dhivehi used | Note |
| --- | --- | --- |
| locale | ލޯކަލް | Transliterated; ބަސް (language) may read better to some. |
| export / exported | އެކްސްޕޯޓް | Transliterated. |
| key (translation key) | ކީ | Transliterated. |
| line (translation line) | ލައިން | Transliterated. |
| translation | ތަރުޖަމާ | |
| missing | ދަތްވޭ / ނެތް | |
| written (a file) | ލިޔުނީ | |
| added / rejected | އިތުރުކުރެވުނު / ބާތިލްކުރެވުނު | |
| report | ރިޕޯޓް | Transliterated. |

The test fixtures' Dhivehi (`tests/Fixtures/app/lang/dv`) is example content, also unreviewed.
