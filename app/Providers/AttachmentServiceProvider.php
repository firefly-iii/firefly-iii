<?php

/**
 * AttachmentServiceProvider.php
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

use FireflyIII\Repositories\Attachment\AttachmentRepository;
use FireflyIII\Repositories\Attachment\AttachmentRepositoryInterface;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * Class AttachmentServiceProvider.
 */
class AttachmentServiceProvider extends ServiceProvider
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
            AttachmentRepositoryInterface::class,
            static function (Application $app) {
                /** @var AttachmentRepositoryInterface $repository */
                $repository = app(AttachmentRepository::class);
                // reference to auth is not understood by phpstan.
                if ($app->auth->check()) { // @phpstan-ignore-line
                    $repository->setUser(auth()->user());
                }

                return $repository;
            }
        );
    }
}
