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

use FireflyIII\Providers\AccountServiceProvider;
use FireflyIII\Providers\AdminServiceProvider;
use FireflyIII\Providers\AppServiceProvider;
use FireflyIII\Providers\AttachmentServiceProvider;
use FireflyIII\Providers\BillServiceProvider;
use FireflyIII\Providers\BudgetServiceProvider;
use FireflyIII\Providers\CategoryServiceProvider;
use FireflyIII\Providers\CurrencyServiceProvider;
use FireflyIII\Providers\EventServiceProvider;
use FireflyIII\Providers\FireflyServiceProvider;
use FireflyIII\Providers\JournalServiceProvider;
use FireflyIII\Providers\PiggyBankServiceProvider;
use FireflyIII\Providers\RecurringServiceProvider;
use FireflyIII\Providers\RouteServiceProvider;
use FireflyIII\Providers\RuleGroupServiceProvider;
use FireflyIII\Providers\RuleServiceProvider;
use FireflyIII\Providers\SearchServiceProvider;
use FireflyIII\Providers\SessionServiceProvider;
use FireflyIII\Providers\TagServiceProvider;
use FireflyIII\Support\Facades\AccountForm;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Facades\CurrencyForm;
use FireflyIII\Support\Facades\ExpandedForm;
use FireflyIII\Support\Facades\FireflyConfig;
use FireflyIII\Support\Facades\Navigation;
use FireflyIII\Support\Facades\PiggyBankForm;
use FireflyIII\Support\Facades\Preferences;
use FireflyIII\Support\Facades\RuleForm;
use FireflyIII\Support\Facades\Steam;
use Illuminate\Auth\AuthServiceProvider;
use Illuminate\Auth\Passwords\PasswordResetServiceProvider;
use Illuminate\Broadcasting\BroadcastServiceProvider;
use Illuminate\Bus\BusServiceProvider;
use Illuminate\Cache\CacheServiceProvider;
use Illuminate\Cookie\CookieServiceProvider;
use Illuminate\Database\DatabaseServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Encryption\EncryptionServiceProvider;
use Illuminate\Filesystem\FilesystemServiceProvider;
use Illuminate\Foundation\Providers\ConsoleSupportServiceProvider;
use Illuminate\Foundation\Providers\FoundationServiceProvider;
use Illuminate\Hashing\HashServiceProvider;
use Illuminate\Mail\MailServiceProvider;
use Illuminate\Notifications\NotificationServiceProvider;
use Illuminate\Pagination\PaginationServiceProvider;
use Illuminate\Pipeline\PipelineServiceProvider;
use Illuminate\Queue\QueueServiceProvider;
use Illuminate\Redis\RedisServiceProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\Translation\TranslationServiceProvider;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\View\ViewServiceProvider;
use PragmaRX\Google2FALaravel\Facade;
use Spatie\Html\Facades\Html;
use TwigBridge\Facade\Twig;
use TwigBridge\ServiceProvider;

return [
    'name'           => envNonEmpty('APP_NAME', 'Firefly III'),
    'env'          => envNonEmpty('APP_ENV', 'production'),
    'debug'          => env('APP_DEBUG', false),
    'url'         => envNonEmpty('APP_URL', 'http://localhost'),
    'timezone'      => envNonEmpty('TZ','UTC'),
    'locale'          => envNonEmpty('DEFAULT_LANGUAGE', 'en_US'),
    'fallback_locale' => 'en_US',
    'key'             => env('APP_KEY'),
    'cipher'          => 'AES-256-CBC',
    'providers'       => [
        // Laravel Framework Service Providers...
        AuthServiceProvider::class,
        BroadcastServiceProvider::class,
        BusServiceProvider::class,
        CacheServiceProvider::class,
        ConsoleSupportServiceProvider::class,
        CookieServiceProvider::class,
        DatabaseServiceProvider::class,
        EncryptionServiceProvider::class,
        FilesystemServiceProvider::class,
        FoundationServiceProvider::class,
        HashServiceProvider::class,
        MailServiceProvider::class,
        NotificationServiceProvider::class,
        PaginationServiceProvider::class,
        PipelineServiceProvider::class,
        QueueServiceProvider::class,
        RedisServiceProvider::class,
        PasswordResetServiceProvider::class,
        SessionServiceProvider::class,
        TranslationServiceProvider::class,
        ValidationServiceProvider::class,
        ViewServiceProvider::class,

        // Package Service Providers...

        // Application Service Providers...
        AppServiceProvider::class,
        FireflyIII\Providers\AuthServiceProvider::class,
        // FireflyIII\Providers\BroadcastServiceProvider::class,
        EventServiceProvider::class,
        RouteServiceProvider::class,

        // own stuff:
        PragmaRX\Google2FALaravel\ServiceProvider::class,
        ServiceProvider::class,

        // More service providers.
        AccountServiceProvider::class,
        AttachmentServiceProvider::class,
        BillServiceProvider::class,
        BudgetServiceProvider::class,
        CategoryServiceProvider::class,
        CurrencyServiceProvider::class,
        FireflyServiceProvider::class,
        JournalServiceProvider::class,
        PiggyBankServiceProvider::class,
        RuleServiceProvider::class,
        RuleGroupServiceProvider::class,
        SearchServiceProvider::class,
        TagServiceProvider::class,
        AdminServiceProvider::class,
        RecurringServiceProvider::class,
    ],
    'aliases'         => [
        'App'           => App::class,
        'Artisan'       => Artisan::class,
        'Auth'          => Auth::class,
        'Blade'         => Blade::class,
        'Broadcast'     => Broadcast::class,
        'Bus'           => Bus::class,
        'Cache'         => Cache::class,
        'Config'        => Config::class,
        'Cookie'        => Cookie::class,
        'Crypt'         => Crypt::class,
        'DB'            => DB::class,
        'Eloquent'      => Model::class,
        'Event'         => Event::class,
        'File'          => File::class,
        'Gate'          => Gate::class,
        'Hash'          => Hash::class,
        'Lang'          => Lang::class,
        'Log'           => Log::class,
        'Mail'          => Mail::class,
        'Notification'  => Notification::class,
        'Password'      => Password::class,
        'Queue'         => Queue::class,
        'Redirect'      => Redirect::class,
        'Redis'         => Redis::class,
        'Request'       => Request::class,
        'Response'      => Response::class,
        'Route'         => Route::class,
        'Schema'        => Schema::class,
        'Session'       => Session::class,
        'Storage'       => Storage::class,
        'URL'           => URL::class,
        'Validator'     => Validator::class,
        'View'          => View::class,
        'Html'          => Html::class,
        'Preferences'   => Preferences::class,
        'FireflyConfig' => FireflyConfig::class,
        'Navigation'    => Navigation::class,
        'Amount'        => Amount::class,
        'Steam'         => Steam::class,
        'ExpandedForm'  => ExpandedForm::class,
        'CurrencyForm'  => CurrencyForm::class,
        'AccountForm'   => AccountForm::class,
        'PiggyBankForm' => PiggyBankForm::class,
        'RuleForm'      => RuleForm::class,
        'Google2FA'     => Facade::class,
        'Twig'          => Twig::class,

        'Arr'           => Arr::class,
        'Http'          => Http::class,
        'Str'           => Str::class,
    ],

    'asset_url'       => env('ASSET_URL', null),

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
