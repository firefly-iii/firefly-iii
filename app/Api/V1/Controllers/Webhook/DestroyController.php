<?php

/*
 * DestroyController.php
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

namespace FireflyIII\Api\V1\Controllers\Webhook;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Webhook;
use FireflyIII\Models\WebhookAttempt;
use FireflyIII\Models\WebhookMessage;
use FireflyIII\Repositories\Webhook\WebhookRepositoryInterface;
use Illuminate\Http\JsonResponse;

/**
 * Class DestroyController
 */
class DestroyController extends Controller
{
    private WebhookRepositoryInterface $repository;

    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->repository = app(WebhookRepositoryInterface::class);
                $this->repository->setUser(auth()->user());

                return $next($request);
            }
        );
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/webhooks/deleteWebhook
     *
     * Remove the specified resource from storage.
     */
    public function destroy(Webhook $webhook): JsonResponse
    {
        $this->repository->destroy($webhook);
        app('preferences')->mark();

        return response()->json([], 204);
    }

    /**
     * This webhook is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/webhooks/deleteWebhookMessageAttempt
     *
     * Remove the specified resource from storage.
     *
     * @throws FireflyException
     */
    public function destroyAttempt(Webhook $webhook, WebhookMessage $message, WebhookAttempt $attempt): JsonResponse
    {
        if ($message->webhook_id !== $webhook->id) {
            throw new FireflyException('200040: Webhook and webhook message are no match');
        }
        if ($attempt->webhook_message_id !== $message->id) {
            throw new FireflyException('200041: Webhook message and webhook attempt are no match');
        }

        $this->repository->destroyAttempt($attempt);
        app('preferences')->mark();

        return response()->json([], 204);
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/webhooks/deleteWebhookMessage
     *
     * Remove the specified resource from storage.
     *
     * @throws FireflyException
     */
    public function destroyMessage(Webhook $webhook, WebhookMessage $message): JsonResponse
    {
        if ($message->webhook_id !== $webhook->id) {
            throw new FireflyException('200040: Webhook and webhook message are no match');
        }
        $this->repository->destroyMessage($message);
        app('preferences')->mark();

        return response()->json([], 204);
    }
}
