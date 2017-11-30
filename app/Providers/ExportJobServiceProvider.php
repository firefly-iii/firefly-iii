<?php
/**
 * ExportJobServiceProvider.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Providers;

use FireflyIII\Repositories\ExportJob\ExportJobRepository;
use FireflyIII\Repositories\ExportJob\ExportJobRepositoryInterface;
use FireflyIII\Repositories\ImportJob\ImportJobRepository;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * Class ExportJobServiceProvider.
 */
class ExportJobServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->exportJob();
        $this->importJob();
    }

    /**
     *
     */
    private function exportJob()
    {
        $this->app->bind(
            ExportJobRepositoryInterface::class,
            function (Application $app) {
                /** @var ExportJobRepository $repository */
                $repository = app(ExportJobRepository::class);
                if ($app->auth->check()) {
                    $repository->setUser(auth()->user());
                }

                return $repository;
            }
        );
    }

    private function importJob()
    {
        $this->app->bind(
            ImportJobRepositoryInterface::class,
            function (Application $app) {
                /** @var ImportJobRepository $repository */
                $repository = app(ImportJobRepository::class);
                if ($app->auth->check()) {
                    $repository->setUser(auth()->user());
                }

                return $repository;
            }
        );
    }
}
