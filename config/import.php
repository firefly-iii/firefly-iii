<?php
declare(strict_types=1);

use FireflyIII\Import\Configuration\BunqConfigurator;
use FireflyIII\Import\Configuration\SpectreConfigurator;
use FireflyIII\Import\JobConfiguration\FakeJobConfiguration;
use FireflyIII\Import\JobConfiguration\FileJobConfiguration;
use FireflyIII\Import\Prerequisites\BunqPrerequisites;
use FireflyIII\Import\Prerequisites\FakePrerequisites;
use FireflyIII\Import\Prerequisites\SpectrePrerequisites;
use FireflyIII\Import\Routine\BunqRoutine;
use FireflyIII\Import\Routine\FakeRoutine;
use FireflyIII\Import\Routine\FileRoutine;
use FireflyIII\Import\Routine\SpectreRoutine;
use FireflyIII\Support\Import\Routine\File\CSVProcessor;

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
        'fake'    => false,
        'file'    => true,
        'bunq'    => true,
        'spectre' => true,
        'plaid'   => false,
        'quovo'   => false,
        'yodlee'  => false,
    ],
    'has_prereq'    => [
        'fake'    => true,
        'file'    => false,
        'bunq'    => true,
        'spectre' => true,
        'plaid'   => true,
        'quovo'   => true,
        'yodlee'  => true,
    ],
    'prerequisites' => [
        'fake'    => FakePrerequisites::class,
        'file'    => false,
        'bunq'    => BunqPrerequisites::class,
        'spectre' => SpectrePrerequisites::class,
        'plaid'   => false,
        'quovo'   => false,
        'yodlee'  => false,
    ],
    'has_config'    => [
        'fake'    => true,
        'file'    => true,
        'bunq'    => true,
        'spectre' => true,
        'plaid'   => true,
        'quovo'   => true,
        'yodlee'  => true,
    ],
    'configuration' => [
        'fake'    => FakeJobConfiguration::class,
        'file'    => FileJobConfiguration::class,
        'bunq'    => BunqConfigurator::class,
        'spectre' => SpectreConfigurator::class,
        'plaid'   => false,
        'quovo'   => false,
        'yodlee'  => false,
    ],
    'routine'       => [
        'fake'    => FakeRoutine::class,
        'file'    => FileRoutine::class,
        'bunq'    => BunqRoutine::class,
        'spectre' => SpectreRoutine::class,
        'plaid'   => false,
        'quovo'   => false,
        'yodlee'  => false,
    ],

    'options'        => [
        'file'    => [
            'import_formats'        => ['csv'], // mt940
            'default_import_format' => 'csv',
            'processors'            => [
                'csv' => CSVProcessor::class,
            ],
        ],
        'bunq'    => [
            'server'  => 'sandbox.public.api.bunq.com', // sandbox.public.api.bunq.com - api.bunq.com
            'version' => 'v1',
        ],
        'spectre' => [
            'server' => 'www.saltedge.com',
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
