<?php
declare(strict_types = 1);


use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\Category;
use FireflyIII\Models\Component;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @SuppressWarnings(PHPMD.ShortMethodName) // method names are mandated by laravel.
 * @SuppressWarnings(PHPMD.TooManyMethods)
 *
 *
 * Down:
 * 1. Create new Components based on Budgets.
 * 2. Create new Components based on Categories
 * 3. Recreate component_id in limits
 * 4. Update all budget_limits entries (component_id).
 * 5. Add the foreign key to component_id in budget_limits
 * 6. Drop column 'budget_id' in budget_limits.
 * 7. Create table journal_components.
 * 8. create entries for budgets in journal_components.
 * 9. create entries for categories in journal_components.
 * 10. drop table budget_journals
 * 11. drop table category_journals
 * 12. drop table budgets
 * 13. drop table categories.
 * 14. rename budget_limits to limits.
 * 15. Rename piggy_bank_events to piggybank_events
 * 16. Rename field 'budget_limit_id' to 'limit_id' in 'limit_repetitions'
 * 17. Do not recreate component_recurring_transaction
 * 18. Do not recreate component_transaction
 * 19. Do not recreate field 'piggybank_id' in 'transactions'
 * 20. Drop fields from currency table.
 *
 *
 *
 * Up:
 *
 * 1. Create new budget table.
 * 2. Create new category table.
 * 3. Create journal_budget table.
 * 4. Create journal_category table.
 * 5. Move budgets to new budgets table AND move journal_components to budget_components.
 * 6. Move categories to categories table AND move journal_components to category_components.
 * 7. Rename limits to budget_limits.
 * 8. Rename piggybank_events to piggy_bank_events
 * 9. Rename field 'limit_id' to 'budget_limit_id' in 'limit_repetitions'
 * 10. Create field budget_id in budget_limits.
 * 11. Update budget_limits with budgets (instead of components).
 * 12. drop table journal_components
 * 13. Drop table component_recurring_transaction
 * 14. Drop table component_transaction
 * 15. Drop field 'piggybank_id' from 'transactions'
 * 16. Drop field 'component_id' from 'budget_limits'
 * 17. Expand currency table with new fields.
 *
 * Class ChangesForV321
 */
class ChangesForV321 extends Migration
{
    public function down()
    {

        $this->moveBudgetsBack(); // 1.
        $this->moveCategoriesBack(); // 2.
        $this->createComponentId(); // 3.
        $this->updateComponentInBudgetLimits(); // 4.
        $this->createComponentIdForeignKey(); // 5.
        $this->dropBudgetIdColumnInBudgetLimits(); // 6.
        $createComponents = new CreateComponentTransactionJournalTable;  // 7.
        $createComponents->up();
        $this->moveBackEntriesForBudgetsInJoinedTable(); // 8.
        $this->moveBackEntriesForCategoriesInJoinedTable(); // 9.
        $this->dropBudgetJournalTable(); // 10.
        $this->dropCategoryJournalTable(); // 11.
        $this->dropBudgetTable(); // 12.
        $this->dropCategoryTable(); // 13.
        $this->renameBudgetLimits(); // 14.
        $this->renamePiggyBankEvents(); // 15.
        $this->renameBudgetLimitToBudgetInRepetitions(); // 16.
        // 17 and then 18 and then 19
        $this->dropFieldsFromCurrencyTable(); // 20.


    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->createBudgetTable(); // 1.
        $this->createCategoryTable(); // 2.
        $this->createBudgetJournalTable(); // 3
        $this->createCategoryJournalTable(); // 4.
        $this->moveBudgets(); // 5.
        $this->moveCategories(); // 6.
        $this->correctNameForBudgetLimits(); // 7.
        $this->correctNameForPiggyBankEvents(); // 8.
        $this->renameBudgetToBudgetLimitInRepetitions(); // 9.
        $this->addBudgetIdFieldToBudgetLimits(); // 10.
        $this->moveComponentIdToBudgetId(); // 11.
        $this->dropComponentJournalTable(); // 12.
        $this->dropComponentRecurringTransactionTable(); // 13.
        $this->dropComponentTransactionTable(); // 14.
        $this->dropPiggyBankIdFromTransactions(); // 15.
        $this->dropComponentIdFromBudgetLimits(); // 16.
        $this->expandCurrencyTable(); // 17.

    }

    private function addBudgetIdFieldToBudgetLimits()
    {
        Schema::table(
            'budget_limits', function (Blueprint $table) {
            $table->integer('budget_id', false, true)->nullable()->after('updated_at');
            $table->foreign('budget_id', 'bid_foreign')->references('id')->on('budgets')->onDelete('cascade');
        }
        );
    }

    private function correctNameForBudgetLimits()
    {
        Schema::rename('limits', 'budget_limits');
    }

    private function correctNameForPiggyBankEvents()
    {
        Schema::rename('piggybank_events', 'piggy_bank_events');

    }

    private function createBudgetJournalTable()
    {
        Schema::create(
            'budget_transaction_journal', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('budget_id')->unsigned();
            $table->integer('transaction_journal_id')->unsigned();
            $table->foreign('budget_id')->references('id')->on('budgets')->onDelete('cascade');
            $table->foreign('transaction_journal_id')->references('id')->on('transaction_journals')->onDelete('cascade');
            $table->unique(['budget_id', 'transaction_journal_id'], 'budid_tjid_unique');
        }
        );
    }

    private function createBudgetTable()
    {
        Schema::create(
            'budgets', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
            $table->string('name', 50);
            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'name']);
        }
        );


    }

    private function createCategoryJournalTable()
    {
        Schema::create(
            'category_transaction_journal', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('category_id')->unsigned();
            $table->integer('transaction_journal_id')->unsigned();
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('transaction_journal_id')->references('id')->on('transaction_journals')->onDelete('cascade');
            $table->unique(['category_id', 'transaction_journal_id'], 'catid_tjid_unique');
        }
        );
    }

    private function createCategoryTable()
    {
        Schema::create(
            'categories', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
            $table->string('name', 50);
            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'name']);
        }
        );
    }

    private function createComponentId()
    {
        Schema::table(
            'budget_limits', function (Blueprint $table) {
            $table->integer('component_id')->unsigned();
        }
        );
    }

    private function createComponentIdForeignKey()
    {
        Schema::table(
            'budget_limits', function (Blueprint $table) {
            $table->foreign('component_id', 'limits_component_id_foreign')->references('id')->on('components')->onDelete('cascade');
        }
        );
    }

    private function dropBudgetIdColumnInBudgetLimits()
    {
        Schema::table(
            'budget_limits', function (Blueprint $table) {
            $table->dropForeign('bid_foreign');
            $table->dropColumn('budget_id'); // also drop foreign key!
        }
        );
    }

    private function dropBudgetJournalTable()
    {
        Schema::dropIfExists('budget_transaction_journal');
    }

    private function dropBudgetTable()
    {
        Schema::dropIfExists('budgets');
    }

    private function dropCategoryJournalTable()
    {
        Schema::dropIfExists('category_transaction_journal');
    }

    private function dropCategoryTable()
    {
        Schema::dropIfExists('categories');
    }

    private function dropComponentIdFromBudgetLimits()
    {
        Schema::table(
            'budget_limits', function (Blueprint $table) {
            $table->dropForeign('limits_component_id_foreign');
            $table->dropColumn('component_id');
        }
        );
    }

    private function dropComponentJournalTable()
    {
        Schema::dropIfExists('component_transaction_journal');
    }

    private function dropComponentRecurringTransactionTable()
    {
        Schema::dropIfExists('component_recurring_transaction');
    }

    private function dropComponentTransactionTable()
    {
        Schema::dropIfExists('component_transaction');
    }

    private function dropFieldsFromCurrencyTable()
    {

        Schema::table(
            'transaction_currencies', function (Blueprint $table) {
            $table->dropColumn('symbol');
            $table->dropColumn('name');
        }
        );
    }

    private function dropPiggyBankIdFromTransactions()
    {

        Schema::table(
            'transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'piggybank_id')) {
                $table->dropForeign('transactions_piggybank_id_foreign');
                $table->dropColumn('piggybank_id');
            }
        }
        );
    }

    private function expandCurrencyTable()
    {
        Schema::table(
            'transaction_currencies', function (Blueprint $table) {
            $table->string('name', 48)->nullable();
            $table->string('symbol', 8)->nullable();
        }
        );
        \DB::update('UPDATE `transaction_currencies` SET `symbol` = "&#8364;", `name` = "Euro" WHERE `code` = "EUR";');
    }

    private function moveBackEntriesForBudgetsInJoinedTable()
    {
        $set = DB::table('budget_transaction_journal')->get();
        /** @var \stdClass $entry */
        foreach ($set as $entry) {
            $budget = Budget::find($entry->budget_id);
            if ($budget) {
                /** @var \FireflyIII\Models\Component $component */
                $component = Component::where('class', 'Budget')->where('name', $budget->name)->where('user_id', $budget->user_id)->first();
                if ($component) {
                    DB::table('component_transaction_journal')->insert(
                        [
                            'component_id'           => $component->id,
                            'transaction_journal_id' => $entry->transaction_journal_id,
                        ]
                    );
                }

            }
        }

    }

    private function moveBackEntriesForCategoriesInJoinedTable()
    {
        $set = DB::table('category_transaction_journal')->get();
        /** @var \stdClass $entry */
        foreach ($set as $entry) {
            $category = Category::find($entry->category_id);
            if ($category) {
                /** @var \FireflyIII\Models\Component $component */
                $component = Component::where('class', 'Category')->where('name', $category->name)->where('user_id', $category->user_id)->first();
                if ($component) {
                    DB::table('component_transaction_journal')->insert(
                        [
                            'component_id'           => $component->id,
                            'transaction_journal_id' => $entry->transaction_journal_id,
                        ]
                    );
                }

            }
        }

    }

    private function moveBudgets()
    {
        Component::where('class', 'Budget')->get()->each(
            function (Component $c) {
                $entry  = [
                    'user_id' => $c->user_id,
                    'name'    => $c->name,

                ];
                $budget = Budget::firstOrCreate($entry);
                // create entry in budget_transaction_journal
                $connections = DB::table('component_transaction_journal')->where('component_id', $c->id)->get();
                /** @var \stdClass $connection */
                foreach ($connections as $connection) {
                    DB::table('budget_transaction_journal')->insert(
                        [
                            'budget_id'              => $budget->id,
                            'transaction_journal_id' => $connection->transaction_journal_id,
                        ]
                    );
                }
            }
        );
    }

    private function moveBudgetsBack()
    {
        Budget::get()->each(
            function (Budget $budget) {
                Component::firstOrCreate(
                    [
                        'name'    => $budget->name,
                        'user_id' => $budget->user_id,
                        'class'   => 'Budget',
                    ]
                );
            }
        );
    }

    private function moveCategories()
    {
        Component::where('class', 'Category')->get()->each(
            function (Component $c) {
                $entry    = [
                    'user_id' => $c->user_id,
                    'name'    => $c->name,

                ];
                $category = Category::firstOrCreate($entry);
                // create entry in category_transaction_journal
                $connections = DB::table('component_transaction_journal')->where('component_id', $c->id)->get();
                /** @var \stdClass $connection */
                foreach ($connections as $connection) {
                    DB::table('category_transaction_journal')->insert(
                        [
                            'category_id'            => $category->id,
                            'transaction_journal_id' => $connection->transaction_journal_id,
                        ]
                    );
                }
            }
        );
    }

    private function moveCategoriesBack()
    {
        Category::get()->each(
            function (Category $category) {
                Component::firstOrCreate(
                    [
                        'name'    => $category->name,
                        'user_id' => $category->user_id,
                        'class'   => 'Category',
                    ]
                );
            }
        );
    }

    private function moveComponentIdToBudgetId()
    {
        BudgetLimit::get()->each(
            function (BudgetLimit $bl) {
                $component = Component::find($bl->component_id);
                if ($component) {
                    $budget = Budget::whereName($component->name)->whereUserId($component->user_id)->first();
                    if ($budget) {
                        $bl->budget_id = $budget->id;
                        $bl->save();
                    }
                }
            }
        );

    }

    private function renameBudgetLimitToBudgetInRepetitions()
    {
        Schema::table(
            'limit_repetitions', function (Blueprint $table) {
            $table->dropForeign('limit_repetitions_budget_limit_id_foreign');
            $table->renameColumn('budget_limit_id', 'limit_id');
            $table->foreign('limit_id')->references('id')->on('limits')->onDelete('cascade');
        }
        );
    }

    private function renameBudgetLimits()
    {
        Schema::rename('budget_limits', 'limits');
    }

    private function renameBudgetToBudgetLimitInRepetitions()
    {
        Schema::table(
            'limit_repetitions', function (Blueprint $table) {
            $table->dropForeign('limit_repetitions_limit_id_foreign');
            $table->renameColumn('limit_id', 'budget_limit_id');
            $table->foreign('budget_limit_id')->references('id')->on('budget_limits')->onDelete('cascade');
        }
        );
    }

    private function renamePiggyBankEvents()
    {
        Schema::rename('piggy_bank_events', 'piggybank_events');

    }

    private function updateComponentInBudgetLimits()
    {
        BudgetLimit::get()->each(
            function (BudgetLimit $bl) {
                $budgetId = $bl->budget_id;
                $budget   = Budget::find($budgetId);
                if ($budget) {
                    $component = Component::where('class', 'Budget')->where('user_id', $budget->user_id)->where('name', $budget->name)->first();
                    if ($component) {
                        $bl->component_id = $component->id;
                        $bl->save();
                    }
                }
            }
        );
    }


}
