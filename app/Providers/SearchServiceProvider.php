<?php

/**
 * SearchServiceProvider.php
 * Copyright (c) 2019 james@firefly-iii.org
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

namespace FireflyIII\Providers;

use FireflyIII\Support\Search\QueryParser\GdbotsQueryParser;
use FireflyIII\Support\Search\OperatorQuerySearch;
use FireflyIII\Support\Search\QueryParser\QueryParser;
use FireflyIII\Support\Search\QueryParser\QueryParserInterface;
use FireflyIII\Support\Search\SearchInterface;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * Class SearchServiceProvider.
 */
class SearchServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void {}

    /**
     * Register the application services.
     */
    #[\Override]
    public function register(): void
    {
        $this->app->bind(
            QueryParserInterface::class,
            static function (): GdbotsQueryParser|QueryParser {
                $implementation = config('search.query_parser');

                return match ($implementation) {
                    'new'   => app(QueryParser::class),
                    default => app(GdbotsQueryParser::class),
                };
            }
        );

        $this->app->bind(
            SearchInterface::class,
            static function (Application $app) {
                /** @var OperatorQuerySearch $search */
                $search = app(OperatorQuerySearch::class);
                if ($app->auth->check()) { // @phpstan-ignore-line (phpstan does not understand the reference to auth)
                    $search->setUser(auth()->user());
                }

                return $search;
            }
        );
    }
}
