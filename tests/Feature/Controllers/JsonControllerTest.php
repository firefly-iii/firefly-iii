<?php
/**
 * JsonControllerTest.php
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Class JsonControllerTest
 *
 * @package Tests\Feature\Controllers
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class JsonControllerTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Controllers\JsonController::action
     * @covers \FireflyIII\Http\Controllers\JsonController::__construct
     */
    public function testAction()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('json.action'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\JsonController::budgets
     */
    public function testBudgets()
    {
        // mock stuff
        $budget        = factory(Budget::class)->make();
        $categoryRepos = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $categoryRepos->shouldReceive('getBudgets')->andReturn(new Collection([$budget]));
        $this->be($this->user());
        $response = $this->get(route('json.budgets'));
        $response->assertStatus(200);
        $response->assertExactJson([$budget->name]);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\JsonController::categories
     */
    public function testCategories()
    {
        // mock stuff
        $category      = factory(Category::class)->make();
        $categoryRepos = $this->mock(CategoryRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $categoryRepos->shouldReceive('getCategories')->andReturn(new Collection([$category]));
        $this->be($this->user());
        $response = $this->get(route('json.categories'));
        $response->assertStatus(200);
        $response->assertExactJson([$category->name]);
    }


    /**
     * @covers \FireflyIII\Http\Controllers\JsonController::tags
     */
    public function testTags()
    {
        // mock stuff
        $tag          = factory(Tag::class)->make();
        $tagRepos     = $this->mock(TagRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $tagRepos->shouldReceive('get')->andReturn(new Collection([$tag]))->once();

        $this->be($this->user());
        $response = $this->get(route('json.tags'));
        $response->assertStatus(200);
        $response->assertExactJson([$tag->tag]);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\JsonController::transactionTypes
     */
    public function testTransactionTypes()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $journalRepos->shouldReceive('getTransactionTypes')->once()->andReturn(new Collection);

        $this->be($this->user());
        $response = $this->get(route('json.transaction-types', ['deposit']));
        $response->assertStatus(200);
        $response->assertExactJson([]);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\JsonController::trigger
     */
    public function testTrigger()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('json.trigger'));
        $response->assertStatus(200);
    }

}
