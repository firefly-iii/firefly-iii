<?php
declare(strict_types=1);

return [
    'enabled'       => [
        'file'    => true,
        'bunq'    => true,
        'spectre' => true,
        'plaid'   => true,
    ],
    'prerequisites' => [
        'file'    => 'FireflyIII\Import\Prerequisites\FilePrerequisites',
        'bunq'    => 'FireflyIII\Import\Prerequisites\BunqPrerequisites',
        'spectre' => 'FireflyIII\Import\Prerequisites\SpectrePrerequisites',
        'plaid'   => 'FireflyIII\Import\Prerequisites\PlaidPrerequisites',

    ],
    'configuration' => [
        'file'    => 'FireflyIII\Import\Configuration\FileConfigurator',
        'bunq'    => 'FireflyIII\Import\Configuration\BunqConfigurator',
        'spectre' => 'FireflyIII\Import\Configuration\SpectreConfigurator',
        'plaid'   => 'FireflyIII\Import\Configuration\PlaidConfigurator',
    ],
    'routine'       => [
        'file'    => 'FireflyIII\Import\Routine\FileRoutine',
        'bunq'    => 'FireflyIII\Import\Routine\BunqRoutine',
        'spectre' => 'FireflyIII\Import\Routine\SpectreRoutine',
        'plaid'   => 'FireflyIII\Import\Routine\PlaidRoutine',
    ],

    'options'        => [
        'file' => [
            'import_formats'        => ['csv'], // mt940
            'default_import_format' => 'csv',
            'processors' => [
                'csv' => 'FireflyIII\Import\FileProcessor\CsvProcessor',
            ],
        ],
    ],
    'default_config' => [
        'file'    => [
            'has-config-file' => true,
            'auto-start'      => false,
        ],
        'bunq'    => [
            'has-config-file' => false,
            'auto-start'      => true,
        ],
        'spectre' => [
            'has-config-file' => false,
            'auto-start'      => true,
        ],
        'plaid'   => [
            'has-config-file' => false,
            'auto-start'      => true,
        ],
    ],
];