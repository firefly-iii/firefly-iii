<?php
/**
 * ReportControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace Tests\Feature\Controllers\Popup;


use Carbon\Carbon;
use Tests\TestCase;

/**
 * Class ReportControllerTest
 *
 * @package Tests\Feature\Controllers\Popup
 */
class ReportControllerTest extends TestCase
{

    /**
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController::general
     */
    public function testBalanceAmount()
    {

        $this->be($this->user());
        $arguments = [
            'attributes' => [
                'location'   => 'balance-amount',
                'startDate'  => Carbon::now()->startOfMonth()->format('Ymd'),
                'endDate'    => Carbon::now()->endOfMonth()->format('Ymd'),
                'accounts'   => 1,
                'accountId'  => 1,
                'categoryId' => 1,
                'budgetId'   => 1,
                'role'       => 3, // diff role, is complicated.
            ],
        ];
        $uri       = route('popup.general') . '?' . http_build_query($arguments);
        $response  = $this->get($uri);
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController::general
     */
    public function testBudgetSpentAmount()
    {

        $this->be($this->user());
        $arguments = [
            'attributes' => [
                'location'   => 'budget-spent-amount',
                'startDate'  => Carbon::now()->startOfMonth()->format('Ymd'),
                'endDate'    => Carbon::now()->endOfMonth()->format('Ymd'),
                'accounts'   => 1,
                'accountId'  => 1,
                'categoryId' => 1,
                'budgetId'   => 1,
            ],
        ];
        $uri       = route('popup.general') . '?' . http_build_query($arguments);
        $response  = $this->get($uri);
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController::general
     */
    public function testCategoryEntry()
    {

        $this->be($this->user());
        $arguments = [
            'attributes' => [
                'location'   => 'category-entry',
                'startDate'  => Carbon::now()->startOfMonth()->format('Ymd'),
                'endDate'    => Carbon::now()->endOfMonth()->format('Ymd'),
                'accounts'   => 1,
                'accountId'  => 1,
                'categoryId' => 1,
                'budgetId'   => 1,
            ],
        ];
        $uri       = route('popup.general') . '?' . http_build_query($arguments);
        $response  = $this->get($uri);
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController::general
     */
    public function testExpenseEntry()
    {

        $this->be($this->user());
        $arguments = [
            'attributes' => [
                'location'   => 'expense-entry',
                'startDate'  => Carbon::now()->startOfMonth()->format('Ymd'),
                'endDate'    => Carbon::now()->endOfMonth()->format('Ymd'),
                'accounts'   => 1,
                'accountId'  => 1,
                'categoryId' => 1,
                'budgetId'   => 1,
            ],
        ];
        $uri       = route('popup.general') . '?' . http_build_query($arguments);
        $response  = $this->get($uri);
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController::general
     */
    public function testIncomeEntry()
    {

        $this->be($this->user());
        $arguments = [
            'attributes' => [
                'location'   => 'income-entry',
                'startDate'  => Carbon::now()->startOfMonth()->format('Ymd'),
                'endDate'    => Carbon::now()->endOfMonth()->format('Ymd'),
                'accounts'   => 1,
                'accountId'  => 1,
                'categoryId' => 1,
                'budgetId'   => 1,
            ],
        ];
        $uri       = route('popup.general') . '?' . http_build_query($arguments);
        $response  = $this->get($uri);
        $response->assertStatus(200);
    }


}
