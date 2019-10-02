<?php

/**
 * ldap.php
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

use Adldap\Schemas\ActiveDirectory;
use Adldap\Schemas\FreeIPA;
use Adldap\Schemas\OpenLDAP;

/*
 * Get schema from .env file.
 */
$schema = OpenLDAP::class;

if ('FreeIPA' === envNonEmpty('ADLDAP_CONNECTION_SCHEME', 'OpenLDAP')) {
    $schema = FreeIPA::class;
}
if ('ActiveDirectory' === envNonEmpty('ADLDAP_CONNECTION_SCHEME', 'OpenLDAP')) {
    $schema = ActiveDirectory::class;
}
return [

    /*
    |--------------------------------------------------------------------------
    | Connections
    |--------------------------------------------------------------------------
    |
    | This array stores the connections that are added to Adldap. You can add
    | as many connections as you like.
    |
    | The key is the name of the connection you wish to use and the value is
    | an array of configuration settings.
    |
    */
    'connections' => [

        'default' => [

            /*
            |--------------------------------------------------------------------------
            | Auto Connect
            |--------------------------------------------------------------------------
            |
            | If auto connect is true, Adldap will try to automatically connect to
            | your LDAP server in your configuration. This allows you to assume
            | connectivity rather than having to connect manually
            | in your application.
            |
            | If this is set to false, you **must** connect manually before running
            | LDAP operations.
            |
            */

            'auto_connect' => env('ADLDAP_AUTO_CONNECT', true),

            /*
            |--------------------------------------------------------------------------
            | Connection
            |--------------------------------------------------------------------------
            |
            | The connection class to use to run raw LDAP operations on.
            |
            | Custom connection classes must implement:
            |
            |  Adldap\Connections\ConnectionInterface
            |
            */

            'connection' => Adldap\Connections\Ldap::class,

            /*
            |--------------------------------------------------------------------------
            | Schema
            |--------------------------------------------------------------------------
            |
            | The schema class to use for retrieving attributes and generating models.
            |
            | You can also set this option to `null` to use the default schema class.
            |
            | For OpenLDAP, you must use the schema:
            |
            |   Adldap\Schemas\OpenLDAP::class
            |
            | For FreeIPA, you must use the schema:
            |
            |   Adldap\Schemas\FreeIPA::class
            |
            | Custom schema classes must implement Adldap\Schemas\SchemaInterface
            |
            */

            'schema' => $schema,

            /*
            |--------------------------------------------------------------------------
            | Connection Settings
            |--------------------------------------------------------------------------
            |
            | This connection settings array is directly passed into the Adldap constructor.
            |
            | Feel free to add or remove settings you don't need.
            |
            */

            'settings' => [

                /*
                |--------------------------------------------------------------------------
                | Account Prefix
                |--------------------------------------------------------------------------
                |
                | The account prefix option is the prefix of your user accounts in LDAP directory.
                |
                | This string is prepended to authenticating users usernames.
                |
                */

                'account_prefix' => env('ADLDAP_ACCOUNT_PREFIX', ''),

                /*
                |--------------------------------------------------------------------------
                | Account Suffix
                |--------------------------------------------------------------------------
                |
                | The account suffix option is the suffix of your user accounts in your LDAP directory.
                |
                | This string is appended to authenticating users usernames.
                |
                */

                'account_suffix' => env('ADLDAP_ACCOUNT_SUFFIX', ''),

                /*
                |--------------------------------------------------------------------------
                | Domain Controllers
                |--------------------------------------------------------------------------
                |
                | The domain controllers option is an array of servers located on your
                | network that serve Active Directory. You can insert as many servers or
                | as little as you'd like depending on your forest (with the
                | minimum of one of course).
                |
                | These can be IP addresses of your server(s), or the host name.
                |
                */

                'hosts' => explode(' ', env('ADLDAP_CONTROLLERS', '127.0.0.1')),

                /*
                |--------------------------------------------------------------------------
                | Port
                |--------------------------------------------------------------------------
                |
                | The port option is used for authenticating and binding to your LDAP server.
                |
                */

                'port' => env('ADLDAP_PORT', 389),

                /*
                |--------------------------------------------------------------------------
                | Timeout
                |--------------------------------------------------------------------------
                |
                | The timeout option allows you to configure the amount of time in
                | seconds that your application waits until a response
                | is received from your LDAP server.
                |
                */

                'timeout' => env('ADLDAP_TIMEOUT', 5),

                /*
                |--------------------------------------------------------------------------
                | Base Distinguished Name
                |--------------------------------------------------------------------------
                |
                | The base distinguished name is the base distinguished name you'd
                | like to perform query operations on. An example base DN would be:
                |
                |        dc=corp,dc=acme,dc=org
                |
                | A correct base DN is required for any query results to be returned.
                |
                */

                'base_dn' => env('ADLDAP_BASEDN', 'dc=temp'),

                /*
                |--------------------------------------------------------------------------
                | Administrator Username & Password
                |--------------------------------------------------------------------------
                |
                | When connecting to your LDAP server, a username and password is required
                | to be able to query and run operations on your server(s). You can
                | use any user account that has these permissions. This account
                | does not need to be a domain administrator unless you
                | require changing and resetting user passwords.
                |
                */

                'username' => env('ADLDAP_ADMIN_USERNAME', ''),
                'password' => env('ADLDAP_ADMIN_PASSWORD', ''),

                /*
                |--------------------------------------------------------------------------
                | Follow Referrals
                |--------------------------------------------------------------------------
                |
                | The follow referrals option is a boolean to tell active directory
                | to follow a referral to another server on your network if the
                | server queried knows the information your asking for exists,
                | but does not yet contain a copy of it locally.
                |
                | This option is defaulted to false.
                |
                */

                'follow_referrals' => env('ADLDAP_FOLLOW_REFFERALS', false),

                /*
                |--------------------------------------------------------------------------
                | SSL & TLS
                |--------------------------------------------------------------------------
                |
                | If you need to be able to change user passwords on your server, then an
                | SSL or TLS connection is required. All other operations are allowed
                | on unsecured protocols.
                |
                | One of these options are definitely recommended if you
                | have the ability to connect to your server securely.
                |
                */

                'use_ssl' => env('ADLDAP_USE_SSL', false),
                'use_tls' => env('ADLDAP_USE_TLS', false),

            ],

        ],

    ],

];
