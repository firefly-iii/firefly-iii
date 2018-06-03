<?php
/**
 * BinderTest.php
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

declare(strict_types=1);

namespace Tests\Unit\Middleware;


use Carbon\Carbon;
use FireflyIII\Helpers\FiscalHelperInterface;
use FireflyIII\Http\Middleware\Binder;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use Illuminate\Support\Collection;
use Route;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Class BinderTest
 * Per object: works, not existing, not logged in + existing
 */
class BinderTest extends TestCase
{

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Account::routeBinder
     */
    public function testAccount(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{account}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\AccountList::routeBinder
     */
    public function testAccountList(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{accountList}', function (Collection $accounts) {
            return 'count: ' . $accounts->count();
        }
        );
        $this->be($this->user());
        $response = $this->get('/_test/binder/1,2');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $response->assertSee('count: 2');
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\AccountList::routeBinder
     */
    public function testAccountListEmpty(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{accountList}', function (Collection $accounts) {
            return 'count: ' . $accounts->count();
        }
        );
        $this->be($this->user());
        $response = $this->get('/_test/binder/');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\AccountList::routeBinder
     */
    public function testAccountListInvalid(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{accountList}', function (Collection $accounts) {
            return 'count: ' . $accounts->count();
        }
        );
        $this->be($this->user());
        $response = $this->get('/_test/binder/0,1,2');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $response->assertSee('count: 2');
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\AccountList::routeBinder
     */
    public function testAccountListNotLoggedIn(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{accountList}', function (Collection $accounts) {
            return 'count: ' . $accounts->count();
        }
        );
        $response = $this->get('/_test/binder/1,2');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Account::routeBinder
     */
    public function testAccountNotFound(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{account}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/0');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Account::routeBinder
     */
    public function testAccountNotLoggedIn(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{account}', function () {
            return 'OK';
        }
        );

        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Attachment::routeBinder
     */
    public function testAttachment(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{attachment}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Attachment::routeBinder
     */
    public function testAttachmentNotFound(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{attachment}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/0');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Attachment::routeBinder
     */
    public function testAttachmentNotLoggedIn(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{attachment}', function () {
            return 'OK';
        }
        );

        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Bill::routeBinder
     */
    public function testBill(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{bill}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Bill::routeBinder
     */
    public function testBillNotFound(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{bill}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/0');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Bill::routeBinder
     */
    public function testBillNotLoggedIn(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{bill}', function () {
            return 'OK';
        }
        );

        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Budget::routeBinder
     */
    public function testBudget(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{budget}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\BudgetLimit::routeBinder
     */
    public function testBudgetLimit(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{budgetLimit}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\BudgetLimit::routeBinder
     */
    public function testBudgetLimitNotFound(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{budgetLimit}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/0');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\BudgetLimit::routeBinder
     */
    public function testBudgetLimitNotLoggedIn(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{budgetLimit}', function () {
            return 'OK';
        }
        );

        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\BudgetList::routeBinder
     */
    public function testBudgetList(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{budgetList}', function (Collection $budgets) {
            return 'count: ' . $budgets->count();
        }
        );
        $this->be($this->user());
        $response = $this->get('/_test/binder/0,1,2');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $response->assertSee('count: 3');
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\BudgetList::routeBinder
     */
    public function testBudgetListInvalid(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{budgetList}', function (Collection $budgets) {
            return 'count: ' . $budgets->count();
        }
        );
        $this->be($this->user());
        $response = $this->get('/_test/binder/-1');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Budget::routeBinder
     */
    public function testBudgetNotFound(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{budget}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/0');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Budget::routeBinder
     */
    public function testBudgetNotLoggedIn(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{budget}', function () {
            return 'OK';
        }
        );

        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Category::routeBinder
     */
    public function testCategory(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{category}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\CategoryList::routeBinder
     */
    public function testCategoryList(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{categoryList}', function (Collection $categories) {
            return 'count: ' . $categories->count();
        }
        );
        $this->be($this->user());
        $response = $this->get('/_test/binder/0,1,2');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $response->assertSee('count: 3');
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\CategoryList::routeBinder
     */
    public function testCategoryListInvalid(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{categoryList}', function (Collection $categories) {
            return 'count: ' . $categories->count();
        }
        );
        $this->be($this->user());
        $response = $this->get('/_test/binder/-1');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Category::routeBinder
     */
    public function testCategoryNotFound(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{category}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/0');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Category::routeBinder
     */
    public function testCategoryNotLoggedIn(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{category}', function () {
            return 'OK';
        }
        );

        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\CurrencyCode::routeBinder
     */
    public function testCurrencyCode(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{fromCurrencyCode}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/USD');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\CurrencyCode::routeBinder
     */
    public function testCurrencyCodeNotFound(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{fromCurrencyCode}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/ABC');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\CurrencyCode::routeBinder
     */
    public function testCurrencyCodeNotLoggedIn(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{fromCurrencyCode}', function () {
            return 'OK';
        }
        );

        $response = $this->get('/_test/binder/EUR');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\Date::routeBinder
     */
    public function testDate(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{date}', function (Carbon $date) {
            return 'date: ' . $date->format('Y-m-d');
        }
        );
        $this->be($this->user());
        $response = $this->get('/_test/binder/20170917');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $response->assertSee('date: 2017-09-17');
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\Date::routeBinder
     */
    public function testDateCurrentMonthEnd(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{date}', function (Carbon $date) {
            return 'date: ' . $date->format('Y-m-d');
        }
        );
        $this->be($this->user());
        $response = $this->get('/_test/binder/currentMonthEnd');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $date = new Carbon;
        $date->endOfMonth();
        $response->assertSee('date: ' . $date->format('Y-m-d'));
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\Date::routeBinder
     */
    public function testDateCurrentMonthStart(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{date}', function (Carbon $date) {
            return 'date: ' . $date->format('Y-m-d');
        }
        );
        $this->be($this->user());
        $response = $this->get('/_test/binder/currentMonthStart');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $date = new Carbon;
        $date->startOfMonth();
        $response->assertSee('date: ' . $date->format('Y-m-d'));
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\Date::routeBinder
     */
    public function testDateCurrentYearEnd(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{date}', function (Carbon $date) {
            return 'date: ' . $date->format('Y-m-d');
        }
        );
        $this->be($this->user());
        $response = $this->get('/_test/binder/currentYearEnd');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $date = new Carbon;
        $date->endOfYear();
        $response->assertSee('date: ' . $date->format('Y-m-d'));
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\Date::routeBinder
     */
    public function testDateCurrentYearStart(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{date}', function (Carbon $date) {
            return 'date: ' . $date->format('Y-m-d');
        }
        );
        $this->be($this->user());
        $response = $this->get('/_test/binder/currentYearStart');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $date = new Carbon;
        $date->startOfYear();
        $response->assertSee('date: ' . $date->format('Y-m-d'));
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\Date::routeBinder
     */
    public function testDateFiscalYearEnd(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{date}', function (Carbon $date) {
            return 'date: ' . $date->format('Y-m-d');
        }
        );

        $date = new Carbon;
        $date->endOfYear();

        // mock fiscal helper:
        $helper = $this->mock(FiscalHelperInterface::class);
        $helper->shouldReceive('endOfFiscalYear')->andReturn($date)->once();

        $this->be($this->user());
        $response = $this->get('/_test/binder/currentFiscalYearEnd');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $response->assertSee('date: ' . $date->format('Y-m-d'));
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\Date::routeBinder
     */
    public function testDateFiscalYearStart(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{date}', function (Carbon $date) {
            return 'date: ' . $date->format('Y-m-d');
        }
        );

        $date = new Carbon;
        $date->startOfYear();

        // mock fiscal helper:
        $helper = $this->mock(FiscalHelperInterface::class);
        $helper->shouldReceive('startOfFiscalYear')->andReturn($date)->once();

        $this->be($this->user());
        $response = $this->get('/_test/binder/currentFiscalYearStart');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $response->assertSee('date: ' . $date->format('Y-m-d'));
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\Date::routeBinder
     */
    public function testDateInvalid(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{date}', function (Carbon $date) {
            return 'date: ' . $date->format('Y-m-d');
        }
        );
        $this->be($this->user());
        $response = $this->get('/_test/binder/fakedate');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\ExportJob::routeBinder
     */
    public function testExportJob(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{exportJob}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/testExport');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\ExportJob::routeBinder
     */
    public function testExportJobNotFound(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{exportJob}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/0');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\ExportJob::routeBinder
     */
    public function testExportJobNotLoggedIn(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{exportJob}', function () {
            return 'OK';
        }
        );

        $response = $this->get('/_test/binder/testExport');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\ImportJob::routeBinder
     */
    public function testImportJob(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{importJob}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/testImport');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\ImportJob::routeBinder
     */
    public function testImportJobNotFound(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{importJob}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/0');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\ImportJob::routeBinder
     */
    public function testImportJobNotLoggedIn(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{importJob}', function () {
            return 'OK';
        }
        );

        $response = $this->get('/_test/binder/testImport');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\JournalList::routeBinder
     */
    public function testJournalList(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{journalList}', function (Collection $journals) {
            return 'count: ' . $journals->count();
        }
        );
        $this->be($this->user());
        $response = $this->get('/_test/binder/1,2');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $response->assertSee('count: 2');
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\JournalList::routeBinder
     */
    public function testJournalListEmpty(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{journalList}', function (Collection $journals) {
            return 'count: ' . $journals->count();
        }
        );
        $this->be($this->user());
        $response = $this->get('/_test/binder/-1');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\LinkType::routeBinder
     */
    public function testLinkType(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{linkType}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\LinkType::routeBinder
     */
    public function testLinkTypeNotFound(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{linkType}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/0');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\LinkType::routeBinder
     */
    public function testLinkTypeNotLoggedIn(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{linkType}', function () {
            return 'OK';
        }
        );

        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\PiggyBank::routeBinder
     */
    public function testPiggyBank(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{piggyBank}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\PiggyBank::routeBinder
     */
    public function testPiggyBankNotFound(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{piggyBank}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/0');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\PiggyBank::routeBinder
     */
    public function testPiggyBankNotLoggedIn(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{piggyBank}', function () {
            return 'OK';
        }
        );

        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Rule::routeBinder
     */
    public function testRule(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{rule}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\RuleGroup::routeBinder
     */
    public function testRuleGroup(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{ruleGroup}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\RuleGroup::routeBinder
     */
    public function testRuleGroupNotFound(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{ruleGroup}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/0');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\RuleGroup::routeBinder
     */
    public function testRuleGroupNotLoggedIn(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{ruleGroup}', function () {
            return 'OK';
        }
        );

        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Rule::routeBinder
     */
    public function testRuleNotFound(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{rule}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/0');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Rule::routeBinder
     */
    public function testRuleNotLoggedIn(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{rule}', function () {
            return 'OK';
        }
        );

        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\TransactionJournal::routeBinder
     */
    public function testTJ(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{tj}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\TransactionJournal::routeBinder
     */
    public function testTJNotFound(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{tj}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/0');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\TransactionJournal::routeBinder
     */
    public function testTJNotLoggedIn(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{tj}', function () {
            return 'OK';
        }
        );

        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Tag::routeBinder
     */
    public function testTag(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{tag}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\TagList::routeBinder
     */
    public function testTagList(): void
    {
        $tagRepos = $this->mock(TagRepositoryInterface::class);
        $tagRepos->shouldReceive('setUser');
        $tags = $this->user()->tags()->whereIn('id', [1, 2])->get(['tags.*']);
        $tagRepos->shouldReceive('get')->once()->andReturn($tags);

        Route::middleware(Binder::class)->any(
            '/_test/binder/{tagList}', function (Collection $tags) {
            return 'count: ' . $tags->count();
        }
        );

        $names = implode(',', $tags->pluck('tag')->toArray());


        $this->be($this->user());
        $response = $this->get('/_test/binder/' . $names);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $response->assertSee('count: 2');
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\TagList::routeBinder
     */
    public function testTagListEmpty(): void
    {
        $tagRepos = $this->mock(TagRepositoryInterface::class);
        $tagRepos->shouldReceive('setUser');
        $tagRepos->shouldReceive('get')->once()->andReturn(new Collection());

        Route::middleware(Binder::class)->any(
            '/_test/binder/{tagList}', function (Collection $tags) {
            return 'count: ' . $tags->count();
        }
        );
        $this->be($this->user());
        $response = $this->get('/_test/binder/noblaexista');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Tag::routeBinder
     */
    public function testTagNotFound(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{tag}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/0');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Tag::routeBinder
     */
    public function testTagNotLoggedIn(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{tag}', function () {
            return 'OK';
        }
        );

        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\TransactionCurrency::routeBinder
     */
    public function testTransactionCurrency(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{currency}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\TransactionCurrency::routeBinder
     */
    public function testTransactionCurrencyNotFound(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{currency}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/0');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\TransactionCurrency::routeBinder
     */
    public function testTransactionCurrencyNotLoggedIn(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{currency}', function () {
            return 'OK';
        }
        );

        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\TransactionJournalLink::routeBinder
     */
    public function testTransactionJournalLink(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{journalLink}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\TransactionJournalLink::routeBinder
     */
    public function testTransactionJournalLinkNotFound(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{journalLink}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/0');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\TransactionJournalLink::routeBinder
     */
    public function testTransactionJournalLinkNotLoggedIn(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{journalLink}', function () {
            return 'OK';
        }
        );

        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\TransactionType::routeBinder
     */
    public function testTransactionType(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{transactionType}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/withdrawal');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\TransactionType::routeBinder
     */
    public function testTransactionTypeNotFound(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{transactionType}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/unknown');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\TransactionType::routeBinder
     */
    public function testTransactionTypeNotLoggedIn(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{transactionType}', function () {
            return 'OK';
        }
        );

        $response = $this->get('/_test/binder/withdrawal');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\UnfinishedJournal::routeBinder
     */
    public function testUnfinishedJournal(): void
    {
        $journal = $this->user()->transactionJournals()->where('completed', 0)->first();
        Route::middleware(Binder::class)->any(
            '/_test/binder/{unfinishedJournal}', function () {
            return 'OK';
        }
        );
        $this->be($this->user());
        $response = $this->get('/_test/binder/' . $journal->id);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\UnfinishedJournal::routeBinder
     */
    public function testUnfinishedJournalFinished(): void
    {
        $journal = $this->user()->transactionJournals()->where('completed', 1)->first();
        Route::middleware(Binder::class)->any(
            '/_test/binder/{unfinishedJournal}', function () {
            return 'OK';
        }
        );
        $response = $this->get('/_test/binder/' . $journal->id);
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\UnfinishedJournal::routeBinder
     */
    public function testUnfinishedJournalNotLoggedIn(): void
    {
        $journal = $this->user()->transactionJournals()->where('completed', 0)->first();
        Route::middleware(Binder::class)->any(
            '/_test/binder/{unfinishedJournal}', function () {
            return 'OK';
        }
        );
        $response = $this->get('/_test/binder/' . $journal->id);
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }


}
