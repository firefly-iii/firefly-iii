<?php

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
    'forbidden_responses' => [
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
    ]
];
