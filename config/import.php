<?php

/**
 * import.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

use FireflyIII\Import\JobConfiguration\FakeJobConfiguration;
use FireflyIII\Import\JobConfiguration\FileJobConfiguration;
use FireflyIII\Import\Prerequisites\FakePrerequisites;
use FireflyIII\Import\Prerequisites\SpectrePrerequisites;
use FireflyIII\Import\Routine\FakeRoutine;
use FireflyIII\Import\Routine\FileRoutine;
use FireflyIII\Support\Import\Routine\File\CSVProcessor;

return [
    // these import providers are available:
    'enabled'          => [
        'fake'    => true,
        'file'    => true,
        'bunq'    => false,
        'spectre' => true,
        'plaid'   => false,
        'quovo'   => false,
        'yodlee'  => false,
    ],
    // demo user can use these import providers (when enabled):
    'allowed_for_demo' => [
        'fake'    => true,
        'file'    => false,
        'bunq'    => false,
        'spectre' => false,
        'plaid'   => false,
        'quovo'   => false,
        'yodlee'  => false,
    ],
    // a normal user user can use these import providers (when enabled):
    'allowed_for_user' => [
        'fake'    => false,
        'file'    => true,
        'bunq'    => true,
        'spectre' => true,
        'plaid'   => true,
        'quovo'   => true,
        'yodlee'  => true,
    ],
    // some providers have pre-requisites.
    'has_prereq'       => [
        'fake'    => true,
        'file'    => false,
        'bunq'    => true,
        'spectre' => true,
        'plaid'   => true,
        'quovo'   => true,
        'yodlee'  => true,
    ],
    // if so, there must be a class to handle them.
    'prerequisites'    => [
        'fake'    => FakePrerequisites::class,
        'file'    => false,
        'bunq'    => false,
        'spectre' => SpectrePrerequisites::class,
        'plaid'   => false,
        'quovo'   => false,
        'yodlee'  => false,
    ],
    // some providers may need extra configuration per job
    'has_job_config'       => [
        'fake'    => true,
        'file'    => true,
        'bunq'    => false,
        'spectre' => false,
        'plaid'   => false,
        'quovo'   => false,
        'yodlee'  => false,
    ],
    // if so, this is the class that handles it.
    'configuration'    => [
        'fake'    => FakeJobConfiguration::class,
        'file'    => FileJobConfiguration::class,
        'bunq'    => false,
        'spectre' => false,
        'plaid'   => false,
        'quovo'   => false,
        'yodlee'  => false,
    ],
    // this is the routine that runs the actual import.
    'routine'          => [
        'fake'    => FakeRoutine::class,
        'file'    => FileRoutine::class,
        'bunq'    => false,
        'spectre' => false,
        'plaid'   => false,
        'quovo'   => false,
        'yodlee'  => false,
    ],

    'options' => [
        'fake'    => [],
        'file'    => [
            'import_formats'        => ['csv'], // mt940
            'default_import_format' => 'csv',
            'processors'            => [
                'csv' => CSVProcessor::class,
            ],
        ],
        'bunq'    => [
            'live'    => [
                'server'  => 'api.bunq.com',
                'version' => 'v1',
            ],
            'sandbox' => [
                'server'  => 'sandbox.public.api.bunq.com', // sandbox.public.api.bunq.com - api.bunq.com
                'version' => 'v1',
            ],
        ],
        'spectre' => [
            'server' => 'www.saltedge.com',
        ],
        'plaid'   => [],
        'quovo'   => [],
        'yodlee'  => [],
    ],
];
