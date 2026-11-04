<?php

return [
    'written' => 'Written',
    'exported' => '{1} Exported :count locale.|[2,*] Exported :count locales.',
    'unknown_format' => 'Unknown format [:format]. Use json or js.',
    'source_missing' => 'The folder [:path] doesn\'t exist.',
    'missing' => [
        'none' => 'Nothing is missing in :locales.',
        'used' => ':locale is missing :count key(s) the code uses:',
        'lines' => ':locale is missing :count line(s) that :base has:',
        'not_exported' => 'JavaScript uses :count key(s) that lang:js doesn\'t export (see js-localization.groups and except):',
        'key' => 'Key',
        'used_in' => 'Used in',
        'base_line' => 'In :base',
        'total' => '{1} :count translation is missing.|[2,*] :count translations are missing.',
    ],
];
