<?php
/**
 * app.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

return [
    'name'            => 'Firefly III',
    'env'             => env('APP_ENV', 'production'),
    'debug'           => env('APP_DEBUG', false),
    'url'             => env('APP_URL', 'http://localhost'),
    'timezone'        => 'UTC',
    'locale'          => 'en_US',
    'fallback_locale' => 'en_US',
    'key'             => env('APP_KEY'),
    'cipher'          => 'AES-256-CBC',
    'log'             => env('APP_LOG', 'errorlog'),
    'log_level'       => env('APP_LOG_LEVEL', 'info'),
    'providers'       => [

        /*
        * Laravel Framework Service Providers...
        */
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Mail\MailServiceProvider::class,
        Illuminate\Notifications\NotificationServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        FireflyIII\Providers\FireflySessionProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,
        Collective\Html\HtmlServiceProvider::class,


        /*
         * Application Service Providers...
         */
        FireflyIII\Providers\LogServiceProvider::class,
        FireflyIII\Providers\AppServiceProvider::class,
        FireflyIII\Providers\AuthServiceProvider::class,
        // FireflyIII\Providers\BroadcastServiceProvider::class,
        FireflyIII\Providers\EventServiceProvider::class,
        FireflyIII\Providers\RouteServiceProvider::class,
        FireflyIII\Providers\FireflyServiceProvider::class,


        // own stuff:
        //Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class,
        //Barryvdh\Debugbar\ServiceProvider::class,
        DaveJamesMiller\Breadcrumbs\ServiceProvider::class,
        TwigBridge\ServiceProvider::class,
        PragmaRX\Google2FA\Vendor\Laravel\ServiceProvider::class,

        /*
         * More service providers.
        */
        FireflyIII\Providers\AccountServiceProvider::class,
        FireflyIII\Providers\AttachmentServiceProvider::class,
        FireflyIII\Providers\BillServiceProvider::class,
        FireflyIII\Providers\BudgetServiceProvider::class,
        FireflyIII\Providers\CategoryServiceProvider::class,
        FireflyIII\Providers\CurrencyServiceProvider::class,
        FireflyIII\Providers\ExportJobServiceProvider::class,
        FireflyIII\Providers\JournalServiceProvider::class,
        FireflyIII\Providers\PiggyBankServiceProvider::class,
        FireflyIII\Providers\RuleServiceProvider::class,
        FireflyIII\Providers\RuleGroupServiceProvider::class,
        FireflyIII\Providers\SearchServiceProvider::class,
        FireflyIII\Providers\TagServiceProvider::class,
        FireflyIII\Providers\AdminServiceProvider::class,


    ],
    'aliases'         => [
        'App'           => Illuminate\Support\Facades\App::class,
        'Artisan'       => Illuminate\Support\Facades\Artisan::class,
        'Auth'          => Illuminate\Support\Facades\Auth::class,
        'Blade'         => Illuminate\Support\Facades\Blade::class,
        'Broadcast'     => Illuminate\Support\Facades\Broadcast::class,
        'Bus'           => Illuminate\Support\Facades\Bus::class,
        'Cache'         => Illuminate\Support\Facades\Cache::class,
        'Config'        => Illuminate\Support\Facades\Config::class,
        'Cookie'        => Illuminate\Support\Facades\Cookie::class,
        'Crypt'         => Illuminate\Support\Facades\Crypt::class,
        'DB'            => Illuminate\Support\Facades\DB::class,
        'Eloquent'      => Illuminate\Database\Eloquent\Model::class,
        'Event'         => Illuminate\Support\Facades\Event::class,
        'File'          => Illuminate\Support\Facades\File::class,
        'Gate'          => Illuminate\Support\Facades\Gate::class,
        'Hash'          => Illuminate\Support\Facades\Hash::class,
        'Lang'          => Illuminate\Support\Facades\Lang::class,
        'Log'           => Illuminate\Support\Facades\Log::class,
        'Mail'          => Illuminate\Support\Facades\Mail::class,
        'Notification'  => Illuminate\Support\Facades\Notification::class,
        'Password'      => Illuminate\Support\Facades\Password::class,
        'Queue'         => Illuminate\Support\Facades\Queue::class,
        'Redirect'      => Illuminate\Support\Facades\Redirect::class,
        'Redis'         => Illuminate\Support\Facades\Redis::class,
        'Request'       => Illuminate\Support\Facades\Request::class,
        'Response'      => Illuminate\Support\Facades\Response::class,
        'Route'         => Illuminate\Support\Facades\Route::class,
        'Schema'        => Illuminate\Support\Facades\Schema::class,
        'Session'       => Illuminate\Support\Facades\Session::class,
        'Storage'       => Illuminate\Support\Facades\Storage::class,
        'URL'           => Illuminate\Support\Facades\URL::class,
        'Validator'     => Illuminate\Support\Facades\Validator::class,
        'View'          => Illuminate\Support\Facades\View::class,
        'Twig'          => TwigBridge\Facade\Twig::class,
        'Form'          => Collective\Html\FormFacade::class,
        'Html'          => Collective\Html\HtmlFacade::class,
        'Breadcrumbs'   => 'DaveJamesMiller\Breadcrumbs\Facade',
        'Preferences'   => 'FireflyIII\Support\Facades\Preferences',
        'FireflyConfig' => 'FireflyIII\Support\Facades\FireflyConfig',
        'Navigation'    => 'FireflyIII\Support\Facades\Navigation',
        'Amount'        => 'FireflyIII\Support\Facades\Amount',
        'Steam'         => 'FireflyIII\Support\Facades\Steam',
        'ExpandedForm'  => 'FireflyIII\Support\Facades\ExpandedForm',
        'Entrust'       => 'Zizaco\Entrust\EntrustFacade',
        'Input'         => 'Illuminate\Support\Facades\Input',
        'Google2FA'     => PragmaRX\Google2FA\Vendor\Laravel\Facade::class,
    ],

];
