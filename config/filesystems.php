<?php
/**
 * filesystems.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

$uploadDisk = [
    'driver' => 'mirror',
    'disks'  => ['local-upload'],
];

$exportDisk = [
    'driver' => 'mirror',
    'disks'  => ['local-export'],
];

// setting the SFTP host is enough to trigger the SFTP option.
if ('' !== env('SFTP_HOST', '')) {
    array_push($uploadDisk['disks'], 'sftp-upload');
    array_push($exportDisk['disks'], 'sftp-export');
}

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

    'default' => env('FILESYSTEM_DRIVER', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Default Cloud Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Many applications store files both locally and in the cloud. For this
    | reason, you may specify a default "cloud" driver here. This driver
    | will be bound as the Cloud disk implementation in the container.
    |
    */

    'cloud' => env('FILESYSTEM_CLOUD', 's3'),

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

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root'   => storage_path('app'),
        ],

        // local storage configuration for upload and export:
        'local-upload' => [
            'driver' => 'local',
            'root'   => storage_path('upload'),
        ],
        'local-export' => [
            'driver' => 'local',
            'root'   => storage_path('export'),
        ],

        // SFTP storage configuration for upload and export:
        'sftp-upload' => [
            'driver'     => 'sftp',
            'host'       => env('SFTP_HOST', '127.0.0.1'),
            'port'       => env('SFTP_PORT', 22),
            'username'   => env('SFTP_USERNAME', 'anonymous'),
            'password'   => env('SFTP_PASSWORD', ''),
            'root'       => env('SFTP_UPLOAD_PATH', ''),
            'privateKey' => env('SFTP_PRIV_KEY'),

            // Optional SFTP Settings
            // 'timeout'       => 30,
            // 'directoryPerm' => 0755,
            // 'permPublic'    => 0644,
            // 'permPrivate'   => 0600,
        ],

        'sftp-export' => [
            'driver'     => 'sftp',
            'host'       => env('SFTP_HOST', '127.0.0.1'),
            'port'       => env('SFTP_PORT', 22),
            'username'   => env('SFTP_USERNAME', 'anonymous'),
            'password'   => env('SFTP_PASSWORD', ''),
            'root'       => env('SFTP_EXPORT_PATH', ''),
            'privateKey' => env('SFTP_PRIV_KEY'),

            // Optional SFTP Settings
            // 'timeout'       => 30,
            // 'directoryPerm' => 0755,
            // 'permPublic'    => 0644,
            // 'permPrivate'   => 0600,
        ],

        // final configuration of upload disk and export disk.
        'upload' => $uploadDisk,
        'export' => $exportDisk,

        // various other paths:
        'database' => [
            'driver' => 'local',
            'root'   => storage_path('database'),
        ],
        'seeds'    => [
            'driver' => 'local',
            'root'   => base_path('resources/seeds'),
        ],
        'stubs'    => [
            'driver' => 'local',
            'root'   => base_path('resources/stubs'),
        ],

        'public' => [
            'driver'     => 'local',
            'root'       => storage_path('app/public'),
            'url'        => env('APP_URL') . '/storage',
            'visibility' => 'public',
        ],

        // unused storage backends.
        /*

        's3' => [
            'driver' => 's3',
            'key'    => env('AWS_KEY'),
            'secret' => env('AWS_SECRET'),
            'region' => env('AWS_REGION'),
            'bucket' => env('AWS_BUCKET'),
        ],

        'sftp' => [
            'driver'        => 'sftp',
            'host'          => 'sftp.example.com',
            'username'      => 'username',
            'password'      => 'password',
            // Optional SFTP Settings
            // 'privateKey'    => 'path/to/or/contents/of/privatekey',
            // 'port'          => 22,
            // 'root'          => '/path/to/root',
            // 'timeout'       => 30,
            // 'directoryPerm' => 0755,
            // 'permPublic'    => 0644,
            // 'permPrivate'   => 0600,
        ],
        
        'rackspace' => [
            'driver'    => 'rackspace',
            'endpoint'  => 'https://identity.api.rackspacecloud.com/v2.0/',
            'username'  => 'your-username',
            'key'       => 'your-key',
            'region'    => 'IAD',
            'url_type'  => 'publicURL',
            'container' => 'your-container',
        ],
        
        'null' => [
            'driver' => 'null',
        ],
        
        'azure' => [
            'driver'      => 'azure',
            'accountName' => 'your-account-name',
            'apiKey'      => 'your-api-key',
            'container'   => 'your-container',
        ],
        
        'gridfs' => [
            'driver'  => 'gridfs',
            'server'  => 'your-server',
            'context' => 'your-context',
            'dbName'  => 'your-db-name',
            // You can also provide other MongoDB connection options here
        ],
        
        'memory' => [
            'driver' => 'memory',
        ],
        
        'phpcr-jackrabbit' => [
            'driver'         => 'phpcr',
            'jackrabbit_url' => 'your-jackrabbit-url',
            'workspace'      => 'your-workspace',
            'root'           => 'your-root',
            // Optional PHPCR Settings
            // 'userId'         => 'your-user-id',
            // 'password'       => 'your-password',
        ],
        
        'phpcr-dbal' => [
            'driver'    => 'phpcr',
            'database'  => 'mysql',
            'workspace' => 'your-workspace',
            'root'      => 'your-root',
            // Optional PHPCR Settings
            // 'userId'    => 'your-user-id',
            // 'password'  => 'your-password',
        ],
        
        'phpcr-prismic' => [
            'driver'      => 'phpcr',
            'prismic_uri' => 'your-prismic-uri',
            'workspace'   => 'your-workspace',
            'root'        => 'your-root',
            // Optional PHPCR Settings
            // 'userId'      => 'your-user-id',
            // 'password'    => 'your-password',
        ],
        
        'replicate' => [
            'driver'  => 'replicate',
            'master'  => 'local',
            'replica' => 's3',
        ],        
        
        'vfs' => [
            'driver' => 'vfs',
        ],
        
        'webdav' => [
            'driver'   => 'webdav',
            'baseUri'  => 'http://example.org/dav/',
            // Optional WebDAV Settings
            // 'userName' => 'user',
            // 'password' => 'password',
            // 'proxy'    => 'locahost:8888',
            // 'authType' => 'digest',  // alternately 'ntlm' or 'basic'
            // 'encoding' => 'all',     // same as ['deflate', 'gzip', 'identity']
        ],
        
        'zip' => [
            'driver' => 'zip',
            'path'   => 'path/to/file.zip',
            // Alternate value if twistor/flysystem-stream-wrapper is available
            // 'path'   => 'local://path/to/file.zip',
        ],
        
        'backblaze' => [
            'driver'          => 'backblaze',
            'account_id'      => 'your-account-id',
            'application_key' => 'your-app-key',
            'bucket'          => 'your-bucket',
        ],
        
        'bos' => [
            'driver'      => 'bos',
            'credentials' => [
                'ak' => 'your-access-key-id',
                'sk' => 'your-secret-access-key',
            ],
            'bucket'      => 'your-bucket',
            // Optional BOS Settings
            // 'endpoint'    => 'http://bj.bcebos.com',
        ],
        
        'clamav' => [
            'driver'    => 'clamav',
            'server'    => 'tcp://127.0.0.1:3310',
            'drive'     => 'local',
            // Optional ClamAV Settings
            // 'copy_scan' => false,
        ],
        
        'cloudinary' => [
            'driver'     => 'cloudinary',
            'api_key'    => env('CLOUDINARY_API_KEY'),
            'api_secret' => env('CLOUDINARY_API_SECRET'),
            'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
        ],
        
        'dropbox' => [
            'driver'    => 'dropbox',
            'authToken' => 'your-auth-token',
        ],
        
        'eloquent' => [
            'driver' => 'eloquent',
            // Optional Eloquent Settings
            // 'model'  => '\Rokde\Flysystem\Adapter\Model\FileModel',
        ],
        
        'fallback' => [
            'driver'   => 'fallback',
            'main'     => 'local',
            'fallback' => 's3',
        ],
        
        'gdrive' => [
            'driver'            => 'gdrive',
            'client_id'         => 'your-client-id',
            'secret'            => 'your-secret',
            'token'             => 'your-token',
            // Optional GDrive Settings
            // 'root'              => 'your-root-directory',
            // 'paths_sheet'       => 'your-paths-sheet',
            // 'paths_cache_drive' => 'local',
        ],
        
        'github' => [
            'driver'  => 'github',
            'project' => 'yourname/project',
            'token'   => 'your-github-token',
        ],
        
        'google' => [
            'driver'     => 'google',
            'project_id' => 'your-project-id',
            'bucket'     => 'your-bucket',
            // Optional Google Cloud Storage Settings
            // 'prefix'     => 'prefix/path/for/drive',
            // 'url'        => 'http://your.custom.cname/',
            // 'key_file'   => 'path/to/file.json',
            //
            // Alternate value if twistor/flysystem-stream-wrapper is available
            // 'key_file'   => 'local://path/to/file.json',
        ],
        
        'http' => [
            'driver'   => 'http',
            'root'     => 'http://example.com',
            // Optional HTTP Settings
            // 'use_head' => true,
            // 'context'  => [],
        ],

        'onedrive' => [
            'driver'       => 'onedrive',
            'access_token' => 'your-access-token',
            // Options only needed for ignited/flysystem-onedrive
            // 'base_url'     => 'https://api.onedrive.com/v1.0/',
            // 'use_logger'   => false,
            // Option only used by nicolasbeauvais/flysystem-onedrive
            // 'root'         => 'root',
        ],
        
        'openstack' => [
            'driver'     => 'openstack',
            'auth_url'   => 'your-auth-url',
            'region'     => 'your-region',
            'user_id'    => 'your-user-id',
            'password'   => 'your-password',
            'project_id' => 'your-project-id',
            'container'  => 'your-container',
        ],
        
        'oss' => [
            'driver'     => 'oss',
            'access_id'  => env('OSS_ACCESS_KEY_ID'),
            'access_key' => env('OSS_ACCESS_KEY_SECRET'),
            'endpoint'   => env('OSS_ENDPOINT'),
            'bucket'     => env('OSS_BUCKET'),
            // Optional OSS Settings
            // 'prefix'     => '',
            // 'region'     => '',    // One of 'hangzhou', 'qingdao', 'beijing', 'hongkong',
            //                        // 'shenzhen', 'shanghai', 'west-1' and 'southeast-1'
        ],
        
        'pdo' => [
            'driver'   => 'pdo',
            'database' => 'default',
        ],
        
        'qcloud' => [
            'driver'     => 'qcloud',
            'app_id'     => 'your-app-id',
            'secret_id'  => 'your-secret-id',
            'secret_key' => 'your-secret-key',
            'bucket'     => 'your-bucket-name',
            'protocol'   => 'https',
            // Optional Tencent/Qcloud COS Settings
            // 'domain'     => 'your-domain',
            // 'timeout'    => 60,
            // 'region'     => 'gz',
        ],
        
        'qiniu' => [
            'driver'    => 'qiniu',
            'accessKey' => 'your-access-key',
            'secretKey' => 'your-secret-key',
            'bucket'    => 'your-bucket',
            'domain'    => 'xxxx.qiniudn.com',
        ],
        
        'redis' => [
            'driver'     => 'redis',
            'connection' => 'default',
        ],
        
        'runabove' => [
            'driver'    => 'runabove',
            'username'  => 'your-username',
            'password'  => 'your-password',
            'tenantId'  => 'your-tenantId',
            // Optional Runabove Settings
            // 'container' => 'container',
            // 'region'    => 'SBG1',   // One of 'SBG1', 'BHS1', and 'GRA1'
        ],
        
        'selectel' => [
            'driver'    => 'selectel',
            'username'  => 'your-username',
            'password'  => 'your-password',
            'container' => 'your-container',
            // Optional Selectel Settings
            // 'domain'    => '',
        ],
        
        'sharefile' => [
            'driver'    => 'sharefile',
            'hostname'  => 'sharefile.example.com',
            'client_id' => 'your-client-id',
            'secret'    => 'your-secret',
            'username'  => 'your-username',
            'password'  => 'your-password',
        ],
        
        'smb' => [
            'driver'   => 'smb',
            'host'     => 'smb.example.com',
            'username' => 'your-username',
            'password' => 'your-password',
            'path'     => 'path/to/shared/directory/for/root',
        ],
        
        'temp' => [
            'driver'  => 'temp',
            // Optional TempDir Settings
            // 'prefix'  => '',
            // 'tempdir' => '/tmp',
        ],
        
        'upyun' => [
            'driver'   => 'upyun',
            'bucket'   => 'your-bucket',
            'operator' => 'operator-name',
            'password' => 'operator-password',
            'protocol' => 'https',
            'domain'   => 'example.b0.upaiyun.com',
        ],
        
        'yandex' => [
            'driver'       => 'yandex',
            'access_token' => 'your-access-token',
            // Optional Yandex Settings
            // 'prefix'       => 'app:/',
        ],
        */
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
