<?php
declare(strict_types=1);

use FireflyIII\Import\Configuration\BunqConfigurator;
use FireflyIII\Import\Configuration\FileConfigurator;
use FireflyIII\Import\Configuration\SpectreConfigurator;
use FireflyIII\Import\FileProcessor\CsvProcessor;
use FireflyIII\Import\Prerequisites\BunqPrerequisites;
use FireflyIII\Import\Prerequisites\FilePrerequisites;
use FireflyIII\Import\Prerequisites\SpectrePrerequisites;
use FireflyIII\Import\Routine\BunqRoutine;
use FireflyIII\Import\Routine\FileRoutine;
use FireflyIII\Import\Routine\SpectreRoutine;

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


return [
    'enabled'       => [
        'file'    => true,
        'bunq'    => true,
        'spectre' => true,
        'plaid'   => false,
    ],
    'prerequisites' => [
        'file'    => FilePrerequisites::class,
        'bunq'    => BunqPrerequisites::class,
        'spectre' => SpectrePrerequisites::class,
        'plaid'   => 'FireflyIII\Import\Prerequisites\PlaidPrerequisites',

    ],
    'configuration' => [
        'file'    => FileConfigurator::class,
        'bunq'    => BunqConfigurator::class,
        'spectre' => SpectreConfigurator::class,
        'plaid'   => 'FireflyIII\Import\Configuration\PlaidConfigurator',
    ],
    'routine'       => [
        'file'    => FileRoutine::class,
        'bunq'    => BunqRoutine::class,
        'spectre' => SpectreRoutine::class,
        'plaid'   => 'FireflyIII\Import\Routine\PlaidRoutine',
    ],

    'options'        => [
        'file' => [
            'import_formats'        => ['csv'], // mt940
            'default_import_format' => 'csv',
            'processors'            => [
                'csv' => CsvProcessor::class,
            ],
        ],
        'bunq' => [
            'server'  => 'sandbox.public.api.bunq.com', // sandbox.public.api.bunq.com - api.bunq.com
            'version' => 'v1',
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
