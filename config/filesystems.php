<?php

/**
 * filesystems.php
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

return [
    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),
    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported: "local", "ftp", "s3", "rackspace", "null", "azure", "copy",
    |            "dropbox", "gridfs", "memory", "phpcr", "replicate", "sftp",
    |            "vfs", "webdav", "zip", "bos", "cloudinary", "eloquent",
    |            "fallback", "github", "gdrive", "google", "mirror", "onedrive",
    |            "oss", "qiniu", "redis", "runabove", "sae", "smb", "temp"
    |
    */

    'disks'   => [
        'local'     => [
            'driver' => 'local',
            'root'   => storage_path('app'),
        ],

        // local storage configuration for upload and export:
        'upload'    => [
            'driver' => 'local',
            'root'   => storage_path('upload'),
        ],
        'export'    => [
            'driver' => 'local',
            'root'   => storage_path('export'),
        ],

        // various other paths:
        'database'  => [
            'driver' => 'local',
            'root'   => storage_path('database'),
        ],
        'seeds'     => [
            'driver' => 'local',
            'root'   => base_path('resources/seeds'),
        ],
        'stubs'     => [
            'driver' => 'local',
            'root'   => base_path('resources/stubs'),
        ],
        'resources' => [
            'driver' => 'local',
            'root'   => base_path('resources'),
        ],

        'public'    => [
            'driver'     => 'local',
            'root'       => storage_path('app/public'),
            'url'        => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Automatically Register Stream Wrappers
    |--------------------------------------------------------------------------
    |
    | This is a list of the filesystem "disks" to automatically register the
    | stream wrappers for on application start.  Any "disk" you don't want to
    | register on every application load will have to be manually referenced
    | before attempting stream access, as the stream wrapper is otherwise only
    | registered when used.
    |
    */
    /*
    // Disabled, pending "twistor/flysystem-stream-wrapper" dependency
    'autowrap' => [
        'local',
    ],
    */
];
