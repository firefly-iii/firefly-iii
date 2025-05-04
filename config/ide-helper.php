<?php

/**
 * ide-helper.php
 * Copyright (c) 2019 james@firefly-iii.org.
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Session\Store;

return [
    /*
    |--------------------------------------------------------------------------
    | Filename & Format
    |--------------------------------------------------------------------------
    |
    | The default filename (without extension) and the format (php or json)
    |
    */

    'filename'                    => '_ide_helper',
    'format'                      => 'php',

    'meta_filename'               => '.phpstorm.meta.php',

    /*
    |--------------------------------------------------------------------------
    | Fluent helpers
    |--------------------------------------------------------------------------
    |
    | Set to true to generate commonly used Fluent methods
    |
    */

    'include_fluent'              => true,

    /*
    |--------------------------------------------------------------------------
    | Write Model Magic methods
    |--------------------------------------------------------------------------
    |
    | Set to false to disable write magic methods of model
    |
    */

    'write_model_magic_where'     => true,

    /*
    |--------------------------------------------------------------------------
    | Write Eloquent Model Mixins
    |--------------------------------------------------------------------------
    |
    | This will add the necessary DocBlock mixins to the model class
    | contained in the Laravel Framework. This helps the IDE with
    | auto-completion.
    |
    | Please be aware that this setting changes a file within the /vendor directory.
    |
    */

    'write_eloquent_model_mixins' => false,

    /*
    |--------------------------------------------------------------------------
    | Helper files to include
    |--------------------------------------------------------------------------
    |
    | Include helper files. By default not included, but can be toggled with the
    | -- helpers (-H) option. Extra helper files can be included.
    |
    */

    'include_helpers'             => false,

    'helper_files'                => [
        base_path().'/vendor/laravel/framework/src/Illuminate/Support/helpers.php',
    ],

    /*
    |--------------------------------------------------------------------------
    | Model locations to include
    |--------------------------------------------------------------------------
    |
    | Define in which directories the ide-helper:models command should look
    | for models.
    |
    */

    'model_locations'             => [
        'app',
    ],

    /*
    |--------------------------------------------------------------------------
    | Extra classes
    |--------------------------------------------------------------------------
    |
    | These implementations are not really extended, but called with magic functions
    |
    */

    'extra'                       => [
        'Eloquent' => [Builder::class, Illuminate\Database\Query\Builder::class],
        'Session'  => [Store::class],
    ],

    'magic'                       => [
        'Log' => [
            'debug'     => 'Monolog\Logger::addDebug',
            'info'      => 'Monolog\Logger::addInfo',
            'notice'    => 'Monolog\Logger::addNotice',
            'warning'   => 'Monolog\Logger::addWarning',
            'error'     => 'Monolog\Logger::addError',
            'critical'  => 'Monolog\Logger::addCritical',
            'alert'     => 'Monolog\Logger::addAlert',
            'emergency' => 'Monolog\Logger::addEmergency',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Interface implementations
    |--------------------------------------------------------------------------
    |
    | These interfaces will be replaced with the implementing class. Some interfaces
    | are detected by the helpers, others can be listed below.
    |
    */

    'interfaces'                  => [
    ],

    /*
    |--------------------------------------------------------------------------
    | Support for custom DB types
    |--------------------------------------------------------------------------
    |
    | This setting allow you to map any custom database type (that you may have
    | created using CREATE TYPE statement or imported using database plugin
    | / extension to a Doctrine type.
    |
    | Each key in this array is a name of the Doctrine2 DBAL Platform. Currently valid names are:
    | 'postgresql', 'db2', 'drizzle', 'mysql', 'oracle', 'sqlanywhere', 'sqlite', 'mssql'
    |
    | This name is returned by getName() method of the specific Doctrine/DBAL/Platforms/AbstractPlatform descendant
    |
    | The value of the array is an array of type mappings. Key is the name of the custom type,
    | (for example, "jsonb" from Postgres 9.4) and the value is the name of the corresponding Doctrine2 type (in
    | our case it is 'json_array'. Doctrine types are listed here:
    | http://doctrine-dbal.readthedocs.org/en/latest/reference/types.html
    |
    | So to support jsonb in your models when working with Postgres, just add the following entry to the array below:
    |
    | "postgresql" => array(
    |       "jsonb" => "json_array",
    |  ),
    |
    */
    'custom_db_types'             => [
    ],

    /*
     |--------------------------------------------------------------------------
     | Support for camel cased models
     |--------------------------------------------------------------------------
     |
     | There are some Laravel packages (such as Eloquence) that allow for accessing
     | Eloquent model properties via camel case, instead of snake case.
     |
     | Enabling this option will support these packages by saving all model
     | properties as camel case, instead of snake case.
     |
     | For example, normally you would see this:
     |
     |  * @property \Illuminate\Support\Carbon $created_at
     |  * @property \Illuminate\Support\Carbon $updated_at
     |
     | With this enabled, the properties will be this:
     |
     |  * @property \Illuminate\Support\Carbon $createdAt
     |  * @property \Illuminate\Support\Carbon $updatedAt
     |
     | Note, it is currently an all-or-nothing option.
     |
     */
    'model_camel_case_properties' => false,

    /*
    |--------------------------------------------------------------------------
    | Property Casts
    |--------------------------------------------------------------------------
    |
    | Cast the given "real type" to the given "type".
    |
    */
    'type_overrides'              => [
        'integer' => 'int',
        'boolean' => 'bool',
    ],

    /*
    |--------------------------------------------------------------------------
    | Include DocBlocks from classes
    |--------------------------------------------------------------------------
    |
    | Include DocBlocks from classes to allow additional code inspection for
    | magic methods and properties.
    |
    */
    'include_class_docblocks'     => false,
];
