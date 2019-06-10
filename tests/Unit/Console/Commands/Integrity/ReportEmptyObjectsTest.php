<?php
/**
 * ReportEmptyObjectsTest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace Tests\Unit\Console\Commands\Integrity;


use FireflyIII\Models\Account;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\Tag;
use Log;
use Tests\TestCase;

/**
 * Class ReportEmptyObjectsTest
 */
class ReportEmptyObjectsTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', get_class($this)));
    }

    /**
     * Run basic test routine.
     *
     * @covers \FireflyIII\Console\Commands\Integrity\ReportEmptyObjects
     */
    public function testHandleBudget(): void
    {
        $user            = $this->user();
        $budget          = Budget::create(
            [
                'user_id' => $user->id,
                'name'    => 'Some budget',
            ]);
        $budgetLine      = sprintf('User #%d (%s) has budget #%d ("%s") which has no transaction journals.',
                                   $user->id, $user->email, $budget->id, $budget->name);
        $budgetLimitLine = sprintf('User #%d (%s) has budget #%d ("%s") which has no budget limits.',
                                   $user->id, $user->email, $budget->id, $budget->name);

        $this->artisan('firefly-iii:report-empty-objects')
             ->expectsOutput($budgetLine)
             ->expectsOutput($budgetLimitLine)
             ->assertExitCode(0);
        $budget->forceDelete();

        // this method changes no objects so there is nothing to verify.
    }

    /**
     * Run basic test routine.
     *
     * @covers \FireflyIII\Console\Commands\Integrity\ReportEmptyObjects
     */
    public function testHandleCategory(): void
    {
        $user         = $this->user();
        $category     = Category::create(
            [
                'user_id' => $user->id,
                'name'    => 'Some category',
            ]);
        $categoryLine = sprintf('User #%d (%s) has category #%d ("%s") which has no transaction journals.',
                                $user->id, $user->email, $category->id, $category->name);

        $this->artisan('firefly-iii:report-empty-objects')
             ->expectsOutput($categoryLine)
             ->assertExitCode(0);
        $category->forceDelete();

        // this method changes no objects so there is nothing to verify.
    }

    /**
     * Run basic test routine.
     *
     * @covers \FireflyIII\Console\Commands\Integrity\ReportEmptyObjects
     */
    public function testHandleTag(): void
    {
        $user    = $this->user();
        $tag     = Tag::create(
            [
                'user_id' => $user->id,
                'tag'     => 'Some tag',
                'tagMode' => 'nothing',
            ]);
        $tagLine = sprintf('User #%d (%s) has tag #%d ("%s") which has no transaction journals.',
                           $user->id, $user->email, $tag->id, $tag->tag);

        $this->artisan('firefly-iii:report-empty-objects')
             ->expectsOutput($tagLine)
             ->assertExitCode(0);
        $tag->forceDelete();

        // this method changes no objects so there is nothing to verify.
    }

    /**
     * Run basic test routine.
     *
     * @covers \FireflyIII\Console\Commands\Integrity\ReportEmptyObjects
     */
    public function testHandleAccount(): void
    {
        $user    = $this->user();
        $account = Account::create(
            [
                'user_id'         => $user->id,
                'name'            => 'Some account',
                'account_type_id' => 1,
            ]);
        $tagLine = sprintf('User #%d (%s) has account #%d ("%s") which has no transactions.',
                           $user->id, $user->email, $account->id, $account->name);

        $this->artisan('firefly-iii:report-empty-objects')
             ->expectsOutput($tagLine)
             ->assertExitCode(0);
        $account->forceDelete();

        // this method changes no objects so there is nothing to verify.
    }


}