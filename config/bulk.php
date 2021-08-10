<?php
declare(strict_types=1);

use FireflyIII\Enums\ClauseType;

return [
    ClauseType::TRANSACTION => [
        ClauseType::WHERE => [
            'source_account_id' => 'required|numeric|belongsToUser:accounts,id',
        ],
        ClauseType::UPDATE => [
            'destination_account_id' => 'required|numeric|belongsToUser:accounts,id',
        ],
    ],
];
