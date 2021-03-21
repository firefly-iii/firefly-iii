<?php

/*
 * DeleteController.php
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
/*
 * DeleteController.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Api\V1\Controllers\Webhook;


use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Webhook;
use FireflyIII\Models\WebhookAttempt;
use FireflyIII\Models\WebhookMessage;
use FireflyIII\Repositories\Webhook\WebhookRepositoryInterface;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;

/**
 * Class DestroyController
 */
class DestroyController extends Controller
{
    private WebhookRepositoryInterface $repository;

    /**
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $admin */
                $admin = auth()->user();

                /** @var WebhookRepositoryInterface repository */
                $this->repository = app(WebhookRepositoryInterface::class);
                $this->repository->setUser($admin);

                return $next($request);
            }
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Webhook $webhook
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function destroy(Webhook $webhook): JsonResponse
    {
        $this->repository->destroy($webhook);

        return response()->json([], 204);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Webhook $webhook
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function destroyAttempt(Webhook $webhook, WebhookMessage $message, WebhookAttempt $attempt): JsonResponse
    {
        if ($message->webhook_id !== $webhook->id) {
            throw new FireflyException('Webhook and webhook message are no match');
        }
        if ($attempt->webhook_message_id !== $message->id) {
            throw new FireflyException('Webhook message and webhook attempt are no match');

        }

        $this->repository->destroyAttempt($attempt);

        return response()->json([], 204);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Webhook $webhook
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function destroyMessage(Webhook $webhook, WebhookMessage $message): JsonResponse
    {
        if ($message->webhook_id !== $webhook->id) {
            throw new FireflyException('Webhook and webhook message are no match');
        }
        $this->repository->destroyMessage($message);

        return response()->json([], 204);
    }


}
