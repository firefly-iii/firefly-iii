<?php

/*
 * twigbridge.php
 * Copyright (c) 2022 james@firefly-iii.org
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

/*
 * This file is part of the TwigBridge package.
 *
 * @copyright Robert Crowe <hello@vivalacrowe.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use FireflyIII\Support\Twig\AmountFormat;
use FireflyIII\Support\Twig\General;
use FireflyIII\Support\Twig\Rule;
use FireflyIII\Support\Twig\TransactionGroupTwig;
use FireflyIII\Support\Twig\Translation;
use Illuminate\Contracts\Support\Htmlable;
use TwigBridge\Extension\Laravel\Auth;
use TwigBridge\Extension\Laravel\Config;
use TwigBridge\Extension\Laravel\Dump;
use TwigBridge\Extension\Laravel\Event;
use TwigBridge\Extension\Laravel\Input;
use TwigBridge\Extension\Laravel\Model;
use TwigBridge\Extension\Laravel\Session;
use TwigBridge\Extension\Laravel\Str;
use TwigBridge\Extension\Laravel\Translator;
use TwigBridge\Extension\Laravel\Url;
use TwigBridge\Extension\Loader\Facades;
use TwigBridge\Extension\Loader\Filters;
use TwigBridge\Extension\Loader\Functions;
use TwigBridge\Extension\Loader\Globals;

// Configuration options for Twig.
return [
    'twig'       => [
        'extension'    => 'twig',
        'environment'  => [
            'debug'            => env('APP_DEBUG', false),
            'charset'          => 'utf-8',
            'cache'            => null,
            'auto_reload'      => true,
            'strict_variables' => false,
            'autoescape'       => 'html',
            'optimizations'    => -1,
        ],
        /*
        |--------------------------------------------------------------------------
        | Safe Classes
        |--------------------------------------------------------------------------
        |
        | When set, the output of the `__string` method of the following classes will not be escaped.
        | default: Laravel's Htmlable, which the HtmlString class implements.
        |
        */
        'safe_classes' => [
            Htmlable::class => ['html'],
        ],

        /*
        |--------------------------------------------------------------------------
        | Global variables
        |--------------------------------------------------------------------------
        |
        | These will always be passed in and can be accessed as Twig variables.
        | NOTE: these will be overwritten if you pass data into the view with the same key.
        |
        */
        'globals'      => [],
    ],

    'extensions' => [
        /*
        |--------------------------------------------------------------------------
        | Extensions
        |--------------------------------------------------------------------------
        |
        | Enabled extensions.
        |
        | `Twig\Extension\DebugExtension` is enabled automatically if twig.debug is TRUE.
        |
        */
        'enabled'   => [
            Facades::class,
            Filters::class,
            Functions::class,
            Event::class,
            Globals::class,
            Auth::class,
            Config::class,
            Dump::class,
            Input::class,
            Session::class,
            Str::class,
            Translator::class,
            Url::class,
            Model::class,
            // Firefly III
            AmountFormat::class,
            General::class,
            Rule::class,
            TransactionGroupTwig::class,
            Translation::class,
        ],

        /*
        |--------------------------------------------------------------------------
        | Facades
        |--------------------------------------------------------------------------
        |
        | Available facades. Access like `{{ Config.get('foo.bar') }}`.
        |
        | Each facade can take an optional array of options. To mark the whole facade
        | as safe you can set the option `'is_safe' => true`. Setting the facade as
        | safe means that any HTML returned will not be escaped.
        |
        | It is advisable to not set the whole facade as safe and instead mark the
        | each appropriate method as safe for security reasons. You can do that with
        | the following syntax:
        |
        | <code>
        |     'Form' => [
        |         'is_safe' => [
        |             'open'
        |         ]
        |     ]
        | </code>
        |
        | The values of the `is_safe` array must match the called method on the facade
        | in order to be marked as safe.
        |
        */
        'facades'   => [
            'Breadcrumbs'   => [
                'is_safe' => [
                    'render',
                ],
            ],
            'Session',
            'Route',
            'Auth',
            'Lang',
            'Preferences',
            'URL',
            'Steam',
            'Config',
            'Request',
            'Html',
            'ExpandedForm'  => [
                'is_safe' => [
                    'date',
                    'text',
                    'select',
                    'balance',
                    'optionsList',
                    'checkbox',
                    'amount',
                    'tags',
                    'integer',
                    'textarea',
                    'location',
                    'file',
                    'staticText',
                    'password',
                    'passwordWithValue',
                    'nonSelectableAmount',
                    'number',
                    'amountNoCurrency',
                    'percentage',
                    'objectGroup',
                ],
            ],
            'AccountForm'   => [
                'is_safe' => [
                    'activeWithdrawalDestinations',
                    'activeDepositDestinations',
                    'assetAccountCheckList',
                    'assetAccountList',
                    'longAccountList',
                    'assetLiabilityMultiAccountList',
                ],
            ],
            'CurrencyForm'  => [
                'is_safe' => [
                    'currencyList',
                    'currencyListEmpty',
                    'balanceAll',
                ],
            ],
            'PiggyBankForm' => [
                'is_safe' => [
                    'piggyBankList',
                ],
            ],
            'RuleForm'      => [
                'is_safe' => [
                    'ruleGroupList',
                    'ruleGroupListWithEmpty',
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Functions
        |--------------------------------------------------------------------------
        |
        | Available functions. Access like `{{ secure_url(...) }}`.
        |
        | Each function can take an optional array of options. These options are
        | passed directly to `Twig\TwigFunction`.
        |
        | So for example, to mark a function as safe you can do the following:
        |
        | <code>
        |     'link_to' => [
        |         'is_safe' => ['html']
        |     ]
        | </code>
        |
        | The options array also takes a `callback` that allows you to name the
        | function differently in your Twig templates than what it's actually called.
        |
        | <code>
        |     'link' => [
        |         'callback' => 'link_to'
        |     ]
        | </code>
        |
        */
        'functions' => [
            'elixir',
            'head',
            'last',
            'mix',
        ],

        /*
        |--------------------------------------------------------------------------
        | Filters
        |--------------------------------------------------------------------------
        |
        | Available filters. Access like `{{ variable|filter }}`.
        |
        | Each filter can take an optional array of options. These options are
        | passed directly to `Twig\TwigFilter`.
        |
        | So for example, to mark a filter as safe you can do the following:
        |
        | <code>
        |     'studly_case' => [
        |         'is_safe' => ['html']
        |     ]
        | </code>
        |
        | The options array also takes a `callback` that allows you to name the
        | filter differently in your Twig templates than what is actually called.
        |
        | <code>
        |     'snake' => [
        |         'callback' => 'snake_case'
        |     ]
        | </code>
        |
        */
        'filters'   => [
            'get' => 'data_get',
        ],
    ],
];
