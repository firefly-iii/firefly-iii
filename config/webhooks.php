<?php


/*
 * webhooks.php
 * Copyright (c) 2025 james@firefly-iii.org
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

// this is hard coded, which is unfortunate.

use FireflyIII\Enums\WebhookResponse;
use FireflyIII\Enums\WebhookTrigger;

return [
    'force_relevant_response' => [
        WebhookTrigger::STORE_TRANSACTION->name         => [
            WebhookTrigger::STORE_BUDGET->name,
            WebhookTrigger::UPDATE_BUDGET->name,
            WebhookTrigger::DESTROY_BUDGET->name,
            WebhookTrigger::STORE_UPDATE_BUDGET_LIMIT->name,

        ],
        WebhookTrigger::UPDATE_TRANSACTION->name        => [
            WebhookTrigger::STORE_BUDGET->name,
            WebhookTrigger::UPDATE_BUDGET->name,
            WebhookTrigger::DESTROY_BUDGET->name,
            WebhookTrigger::STORE_UPDATE_BUDGET_LIMIT->name,
        ],
        WebhookTrigger::DESTROY_TRANSACTION->name       => [
            WebhookTrigger::STORE_BUDGET->name,
            WebhookTrigger::UPDATE_BUDGET->name,
            WebhookTrigger::DESTROY_BUDGET->name,
            WebhookTrigger::STORE_UPDATE_BUDGET_LIMIT->name,
        ],
        WebhookTrigger::STORE_BUDGET->name              => [
            WebhookTrigger::STORE_TRANSACTION->name,
            WebhookTrigger::UPDATE_TRANSACTION->name,
            WebhookTrigger::DESTROY_TRANSACTION->name,

        ],
        WebhookTrigger::UPDATE_BUDGET->name             => [
            WebhookTrigger::STORE_TRANSACTION->name,
            WebhookTrigger::UPDATE_TRANSACTION->name,
            WebhookTrigger::DESTROY_TRANSACTION->name,
        ],
        WebhookTrigger::DESTROY_BUDGET->name            => [
            WebhookTrigger::STORE_TRANSACTION->name,
            WebhookTrigger::UPDATE_TRANSACTION->name,
            WebhookTrigger::DESTROY_TRANSACTION->name,
        ],
        WebhookTrigger::STORE_UPDATE_BUDGET_LIMIT->name => [
            WebhookTrigger::STORE_TRANSACTION->name,
            WebhookTrigger::UPDATE_TRANSACTION->name,
            WebhookTrigger::DESTROY_TRANSACTION->name,
        ],
    ],
    'forbidden_responses'     => [
        WebhookTrigger::ANY->name                       => [
            WebhookResponse::BUDGET->name,
            WebhookResponse::TRANSACTIONS->name,
            WebhookResponse::ACCOUNTS->name,
        ],
        WebhookTrigger::STORE_TRANSACTION->name         => [
            WebhookResponse::BUDGET->name,
        ],
        WebhookTrigger::UPDATE_TRANSACTION->name        => [
            WebhookResponse::BUDGET->name,
        ],
        WebhookTrigger::DESTROY_TRANSACTION->name       => [
            WebhookResponse::BUDGET->name,
        ],
        WebhookTrigger::STORE_BUDGET->name              => [
            WebhookResponse::TRANSACTIONS->name,
            WebhookResponse::ACCOUNTS->name,

        ],
        WebhookTrigger::UPDATE_BUDGET->name             => [
            WebhookResponse::TRANSACTIONS->name,
            WebhookResponse::ACCOUNTS->name,
        ],
        WebhookTrigger::DESTROY_BUDGET->name            => [
            WebhookResponse::TRANSACTIONS->name,
            WebhookResponse::ACCOUNTS->name,
        ],
        WebhookTrigger::STORE_UPDATE_BUDGET_LIMIT->name => [
            WebhookResponse::TRANSACTIONS->name,
            WebhookResponse::ACCOUNTS->name,
        ],
    ],
];
