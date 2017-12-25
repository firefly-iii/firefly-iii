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

namespace Tests\Unit\Helpers;


use FireflyIII\Http\Middleware\Binder;
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\Account::routeBinder
     */
    public function testAccount()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Support\Binder\AccountList::routeBinder
     */
    public function testAccountList()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Support\Binder\AccountList::routeBinder
     */
    public function testAccountListEmpty()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Support\Binder\AccountList::routeBinder
     */
    public function testAccountListInvalid()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Support\Binder\AccountList::routeBinder
     */
    public function testAccountListNotLoggedIn()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\Account::routeBinder
     */
    public function testAccountNotFound()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\Account::routeBinder
     */
    public function testAccountNotLoggedIn()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\Attachment::routeBinder
     */
    public function testAttachment()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\Attachment::routeBinder
     */
    public function testAttachmentNotFound()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\Attachment::routeBinder
     */
    public function testAttachmentNotLoggedIn()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\Bill::routeBinder
     */
    public function testBill()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\Bill::routeBinder
     */
    public function testBillNotFound()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\Bill::routeBinder
     */
    public function testBillNotLoggedIn()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\Budget::routeBinder
     */
    public function testBudget()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\BudgetLimit::routeBinder
     */
    public function testBudgetLimit()
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{budgetlimit}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\BudgetLimit::routeBinder
     */
    public function testBudgetLimitNotFound()
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{budgetlimit}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/0');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\BudgetLimit::routeBinder
     */
    public function testBudgetLimitNotLoggedIn()
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{budgetlimit}', function () {
            return 'OK';
        }
        );

        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\Budget::routeBinder
     */
    public function testBudgetNotFound()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\Budget::routeBinder
     */
    public function testBudgetNotLoggedIn()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\Category::routeBinder
     */
    public function testCategory()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\Category::routeBinder
     */
    public function testCategoryNotFound()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\Category::routeBinder
     */
    public function testCategoryNotLoggedIn()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Support\Binder\CurrencyCode::routeBinder
     */
    public function testCurrencyCode()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Support\Binder\CurrencyCode::routeBinder
     */
    public function testCurrencyCodeNotFound()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Support\Binder\CurrencyCode::routeBinder
     */
    public function testCurrencyCodeNotLoggedIn()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\ExportJob::routeBinder
     */
    public function testExportJob()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\ExportJob::routeBinder
     */
    public function testExportJobNotFound()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\ExportJob::routeBinder
     */
    public function testExportJobNotLoggedIn()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\ImportJob::routeBinder
     */
    public function testImportJob()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\ImportJob::routeBinder
     */
    public function testImportJobBadStatus()
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{importJob}', function () {
            return 'OK';
        }
        );
        $this->be($this->user());
        $response = $this->get('/_test/binder/bad-status');
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\ImportJob::routeBinder
     */
    public function testImportJobNotFound()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\ImportJob::routeBinder
     */
    public function testImportJobNotLoggedIn()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\LinkType::routeBinder
     */
    public function testLinkType()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\LinkType::routeBinder
     */
    public function testLinkTypeNotFound()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\LinkType::routeBinder
     */
    public function testLinkTypeNotLoggedIn()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\PiggyBank::routeBinder
     */
    public function testPiggyBank()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\PiggyBank::routeBinder
     */
    public function testPiggyBankNotFound()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\PiggyBank::routeBinder
     */
    public function testPiggyBankNotLoggedIn()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\Rule::routeBinder
     */
    public function testRule()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\RuleGroup::routeBinder
     */
    public function testRuleGroup()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\RuleGroup::routeBinder
     */
    public function testRuleGroupNotFound()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\RuleGroup::routeBinder
     */
    public function testRuleGroupNotLoggedIn()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\Rule::routeBinder
     */
    public function testRuleNotFound()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\Rule::routeBinder
     */
    public function testRuleNotLoggedIn()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\TransactionJournal::routeBinder
     */
    public function testTJ()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\TransactionJournal::routeBinder
     */
    public function testTJNotFound()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\TransactionJournal::routeBinder
     */
    public function testTJNotLoggedIn()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\Tag::routeBinder
     */
    public function testTag()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\Tag::routeBinder
     */
    public function testTagNotFound()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\Tag::routeBinder
     */
    public function testTagNotLoggedIn()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\TransactionCurrency::routeBinder
     */
    public function testTransactionCurrency()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\TransactionCurrency::routeBinder
     */
    public function testTransactionCurrencyNotFound()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\TransactionCurrency::routeBinder
     */
    public function testTransactionCurrencyNotLoggedIn()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\TransactionJournalLink::routeBinder
     */
    public function testTransactionJournalLink()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\TransactionJournalLink::routeBinder
     */
    public function testTransactionJournalLinkNotFound()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\TransactionJournalLink::routeBinder
     */
    public function testTransactionJournalLinkNotLoggedIn()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\TransactionType::routeBinder
     */
    public function testTransactionType()
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{transaction_type}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/withdrawal');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\TransactionType::routeBinder
     */
    public function testTransactionTypeNotFound()
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{transaction_type}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/unknown');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Models\TransactionType::routeBinder
     */
    public function testTransactionTypeNotLoggedIn()
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{transaction_type}', function () {
            return 'OK';
        }
        );

        $response = $this->get('/_test/binder/withdrawal');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Support\Binder\UnfinishedJournal::routeBinder
     */
    public function testUnfinishedJournal()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Support\Binder\UnfinishedJournal::routeBinder
     */
    public function testUnfinishedJournalFinished()
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
     * @covers \FireflyIII\Http\Middleware\Binder::handle
     * @covers \FireflyIII\Http\Middleware\Binder::__construct
     * @covers \FireflyIII\Http\Middleware\Binder::performBinding
     * @covers \FireflyIII\Support\Binder\UnfinishedJournal::routeBinder
     */
    public function testUnfinishedJournalNotLoggedIn()
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