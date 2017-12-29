<?php
/**
 * import.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

return [
    'enabled'       => [
        'file'    => true,
        'bunq'    => false,
        'spectre' => true,
        'plaid'   => false,
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
