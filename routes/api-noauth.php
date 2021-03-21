<?php

declare(strict_types=1);

// Cron job API routes:
Route::group(
    [
        'namespace'  => 'FireflyIII\Api\V1\Controllers\System', 'prefix' => '',
        'as'         => 'api.v1.cron.'],
    static function () {
        Route::get('{cliToken}', ['uses' => 'CronController@cron', 'as' => 'index']);
    }
);