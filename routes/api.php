<?php
/**
 * api.php
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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

Route::group(
    ['middleware' => ['auth:api','bindings'], 'namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'bill', 'as' => 'api.v1.bills.'], function () {

    // Bills API routes:
    Route::get('', ['uses' => 'BillController@index', 'as' => 'index']);
    Route::post('', ['uses' => 'BillController@store', 'as' => 'store']);
    Route::get('{bill}', ['uses' => 'BillController@show', 'as' => 'show']);
    Route::put('{bill}', ['uses' => 'BillController@update', 'as' => 'update']);
    Route::delete('{bill}', ['uses' => 'BillController@delete', 'as' => 'delete']);
}
);
