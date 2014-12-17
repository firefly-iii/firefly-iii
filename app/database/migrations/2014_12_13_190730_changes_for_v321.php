<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;

/**
 * SuppressWarnings(PHPMD.ShortMethodName)
 *
 * Class ChangesForV321
 */
class ChangesForV321 extends Migration
{
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        /*
         * Undo updates to budget_limits table.
         */
        $this->undoBudgetLimits();

        $this->undoPiggyBankEvents();

        $this->undoMoveBudgets();

        $this->undoMoveCategories();

        $this->undoCreateBudgetTables();

        $this->undoCreateCategoryTables();

        $this->undoRenameInLimitRepetitions();

        $this->undoUpdateTransactionTable();

        $this->undoDropCompRecurTable();

        $this->undoDropCompTransTable();
    }

    public function undoBudgetLimits()
    {
        Schema::table(
            'budget_limits', function (Blueprint $table) {
            $table->dropForeign('bid_foreign');
            $table->dropColumn('budget_id');
        }
        );

        Schema::rename('budget_limits', 'limits');
    }

    public function undoPiggyBankEvents()
    {
        Schema::rename('piggy_bank_events', 'piggybank_events');

    }

    public function undoMoveBudgets()
    {
        Budget::get()->each(
            function (Budget $budget) {
                $entry     = [
                    'user_id' => $budget->user_id,
                    'name'    => $budget->name,
                    'class'   => 'Budget'

                ];
                $component = Component::firstOrCreate($entry);
                Log::debug('Migrated budget back #' . $component->id . ': ' . $component->name);
                // create entry in component_transaction_journal
                $connections = DB::table('budget_transaction_journal')->where('budget_id', $budget->id)->get();
                foreach ($connections as $connection) {
                    try {
                        DB::table('component_transaction_journal')->insert(
                            [
                                'component_id'           => $component->id,
                                'transaction_journal_id' => $connection->transaction_journal_id
                            ]
                        );
                    } catch (QueryException $e) {
                    }
                }
            }
        );
    }

    public function undoMoveCategories()
    {
        Category::get()->each(
            function (Category $category) {
                $entry     = [
                    'user_id' => $category->user_id,
                    'name'    => $category->name,
                    'class'   => 'Category'

                ];
                $component = Component::firstOrCreate($entry);
                Log::debug('Migrated category back #' . $component->id . ': ' . $component->name);
                // create entry in component_transaction_journal
                $connections = DB::table('category_transaction_journal')->where('category_id', $category->id)->get();
                foreach ($connections as $connection) {
                    try {
                        DB::table('component_transaction_journal')->insert(
                            [
                                'component_id'           => $component->id,
                                'transaction_journal_id' => $connection->transaction_journal_id
                            ]
                        );
                    } catch (QueryException $e) {
                    }
                }
            }
        );
    }

    public function undoCreateBudgetTables()
    {
        Schema::drop('budget_transaction_journal');
        Schema::drop('budgets');
    }

    public function undoCreateCategoryTables()
    {
        Schema::drop('category_transaction_journal');
        Schema::drop('categories');
    }

    public function undoRenameInLimitRepetitions()
    {
        Schema::table(
            'limit_repetitions', function (Blueprint $table) {
            $table->renameColumn('budget_limit_id', 'limit_id');
        }
        );
    }

    public function undoUpdateTransactionTable()
    {
        Schema::table(
            'transactions', function (Blueprint $table) {
            $table->integer('piggybank_id')->nullable()->unsigned();
            $table->foreign('piggybank_id')->references('id')->on('piggybanks')->onDelete('set null');
        }
        );
    }

    public function undoDropCompRecurTable()
    {
        Schema::create(
            'component_recurring_transaction', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('component_id')->unsigned();
            $table->integer('recurring_transaction_id')->unsigned();
            $table->boolean('optional');
            $table->foreign('component_id')->references('id')->on('components')->onDelete('cascade');
            $table->foreign('recurring_transaction_id')->references('id')->on('recurring_transactions')->onDelete('cascade');
            $table->unique(['component_id', 'recurring_transaction_id'], 'cid_rtid_unique');

        }
        );
    }

    public function undoDropCompTransTable()
    {
        Schema::create(
            'component_transaction', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('component_id')->unsigned();
            $table->integer('transaction_id')->unsigned();
            $table->foreign('component_id')->references('id')->on('components')->onDelete('cascade');
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');
            $table->unique(['component_id', 'transaction_id']);
        }
        );
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->doCreateBudgetTables();
        $this->doRenameInLimitRepetitions();
        $this->doBudgetLimits();
        $this->doPiggyBankEvents();
        $this->doCreateCategoryTables();
        $this->doUpdateTransactionTable();
        $this->doDropCompRecurTable();
        $this->doDropCompTransTable();
        $this->doMoveBudgets();
        $this->doMoveCategories();


    }

    public function doCreateBudgetTables()
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
        Schema::create(
            'budget_transaction_journal', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('budget_id')->unsigned();
            $table->integer('transaction_journal_id')->unsigned();
            $table->foreign('budget_id')->references('id')->on('budgets')->onDelete('cascade');
            $table->foreign('transaction_journal_id')->references('id')->on('transaction_journals')->onDelete('cascade');
            $table->unique(['budget_id', 'transaction_journal_id'], 'bid_tjid_unique');
        }
        );
    }

    public function doRenameInLimitRepetitions()
    {
        Schema::table(
            'limit_repetitions', function (Blueprint $table) {
            $table->renameColumn('limit_id', 'budget_limit_id');
        }
        );
    }

    public function doBudgetLimits()
    {
        Schema::rename('limits', 'budget_limits');
        Schema::table(
            'budget_limits', function (Blueprint $table) {
            $table->integer('budget_id')->unsigned()->after('updated_at');
            $table->foreign('budget_id', 'bid_foreign')->references('id')->on('budgets')->onDelete('cascade');
        }
        );
    }

    public function doPiggyBankEvents()
    {
        Schema::rename('piggybank_events', 'piggy_bank_events');

    }

    public function doCreateCategoryTables()
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

    public function doUpdateTransactionTable()
    {
        Schema::table(
            'transactions', function (Blueprint $table) {
            $table->dropForeign('transactions_piggybank_id_foreign');
            #$table->dropIndex('transactions_piggybank_id_foreign');
            $table->dropColumn('piggybank_id');
        }
        );
    }

    public function doDropCompRecurTable()
    {
        Schema::drop('component_recurring_transaction');
    }

    public function doDropCompTransTable()
    {
        Schema::drop('component_transaction');
    }

    public function doMoveBudgets()
    {
        Component::where('class', 'Budget')->get()->each(
            function (Component $c) {
                $entry  = [
                    'user_id' => $c->user_id,
                    'name'    => $c->name

                ];
                $budget = Budget::firstOrCreate($entry);
                Log::debug('Migrated budget #' . $budget->id . ': ' . $budget->name);
                // create entry in budget_transaction_journal
                $connections = DB::table('component_transaction_journal')->where('component_id', $c->id)->get();
                foreach ($connections as $connection) {
                    DB::table('budget_transaction_journal')->insert(
                        [
                            'budget_id'              => $budget->id,
                            'transaction_journal_id' => $connection->transaction_journal_id
                        ]
                    );
                }
            }
        );
    }

    public function doMoveCategories()
    {
        Component::where('class', 'Category')->get()->each(
            function (Component $c) {
                $entry    = [
                    'user_id' => $c->user_id,
                    'name'    => $c->name

                ];
                $category = Category::firstOrCreate($entry);
                Log::debug('Migrated category #' . $category->id . ': ' . $category->name);
                // create entry in category_transaction_journal
                $connections = DB::table('component_transaction_journal')->where('component_id', $c->id)->get();
                foreach ($connections as $connection) {
                    DB::table('category_transaction_journal')->insert(
                        [
                            'category_id'            => $category->id,
                            'transaction_journal_id' => $connection->transaction_journal_id
                        ]
                    );
                }
            }
        );
    }

}
