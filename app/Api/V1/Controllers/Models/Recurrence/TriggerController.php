<?php
/*
 * TriggerController.php
 * Copyright (c) 2021 james@firefly-iii.org
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

namespace FireflyIII\Api\V1\Controllers\Models\Recurrence;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use FireflyIII\Support\Cronjobs\RecurringCronjob;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Log;

/**
 * Class TriggerController
 */
class TriggerController extends Controller
{
    private RecurringRepositoryInterface $repository;

    /**
     * RecurrenceController constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $user */
                $user = auth()->user();

                /** @var RecurringRepositoryInterface repository */
                $this->repository = app(RecurringRepositoryInterface::class);
                $this->repository->setUser($user);

                return $next($request);
            }
        );
    }

    /**
     * @return JsonResponse
     * @throws FireflyException
     * @codeCoverageIgnore
     *
     * TODO currently unused + unreachable.
     */
    public function trigger(): JsonResponse
    {
        /** @var RecurringCronjob $recurring */
        $recurring = app(RecurringCronjob::class);
        try {
            $result = $recurring->fire();
        } catch (FireflyException $e) {
            Log::error($e->getMessage());
            throw new FireflyException('200022: Error in cron job.', 0, $e);
        }
        if (false === $result) {
            return response()->json([], 204);
        }

        return response()->json();
    }
}
