<?php

/**
 * app.php
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

use FireflyIII\Support\Facades\AccountForm;
use FireflyIII\Support\Facades\CurrencyForm;
use FireflyIII\Support\Facades\ExpandedForm;
use FireflyIII\Support\Facades\PiggyBankForm;
use FireflyIII\Support\Facades\RuleForm;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Spatie\Html\Facades\Html;

return [
    'name'            => envNonEmpty('APP_NAME', 'Firefly III'),
    'env'             => envNonEmpty('APP_ENV', 'production'),
    'debug'           => env('APP_DEBUG', false),
    'url'             => envNonEmpty('APP_URL', 'http://localhost'),
    'timezone'        => envNonEmpty('TZ', 'UTC'),
    'locale'          => envNonEmpty('DEFAULT_LANGUAGE', 'en_US'),
    'fallback_locale' => 'en_US',
    'key'             => env('APP_KEY'),
    'cipher'          => 'AES-256-CBC',
    'aliases'         => [
        'Auth'          => Auth::class,
        'Route'         => Route::class,
        'Config'        => Config::class,
        'Session'       => Session::class,
        'URL'           => URL::class,
        'Html'          => Html::class,
        'Lang'          => Lang::class,
        'AccountForm'   => AccountForm::class,
        'CurrencyForm'  => CurrencyForm::class,
        'ExpandedForm'  => ExpandedForm::class,
        'PiggyBankForm' => PiggyBankForm::class,
        'RuleForm'      => RuleForm::class,
    ],

    'asset_url'       => env('ASSET_URL'),

    /*
    |--------------------------------------------------------------------------
    | Faker Locale
    |--------------------------------------------------------------------------
    |
    | This locale will be used by the Faker PHP library when generating fake
    | data for your database seeds. For example, this will be used to get
    | localized telephone numbers, street address information and more.
    |
    */

    'faker_locale'    => 'en_US',
];
