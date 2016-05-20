<?php
declare(strict_types = 1);


use FireflyIII\Models\BudgetLimit;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class ChangesForV390
 */
class ChangesForV390 extends Migration
{
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // restore removed unique index. Recreate it the correct way:
        Schema::table(
            'budget_limits', function (Blueprint $table) {
            $table->unique(['budget_id', 'startdate', 'repeat_freq'], 'unique_bl_combi');
        }
        );


        $backup = $this->backupRepeatFreqsFromString();

        // drop string and create enum field
        Schema::table(
            'budget_limits', function (Blueprint $table) {
            $table->dropColumn('repeat_freq');
        }
        );
        Schema::table(
            'budget_limits', function (Blueprint $table) {
            $table->enum('repeat_freq', ['daily', 'weekly', 'monthly', 'quarterly', 'half-year', 'yearly']);
        }
        );

        // restore backup. Change unknowns to "monthly".
        $this->restoreRepeatFreqsToEnum($backup);

        // drop budget <> transaction table:
        Schema::dropIfExists('budget_transaction');

        // drop category <> transaction table:
        Schema::dropIfExists('category_transaction');

    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //        // remove an index.
        Schema::table(
            'budget_limits', function (Blueprint $table) {
            $table->dropForeign('bid_foreign');
        }
        );

        Schema::table(
            'budget_limits', function (Blueprint $table) {
            $table->dropUnique('unique_limit');
            $table->dropUnique('unique_bl_combi');
        }
        );


        // recreate foreign key:
        Schema::table(
            'budget_limits', function (Blueprint $table) {
            $table->foreign('budget_id', 'bid_foreign')->references('id')->on('budgets')->onDelete('cascade');
        }
        );


        // backup values
        $backup = $this->backupRepeatFreqsFromEnum();

        // drop enum and create varchar field
        Schema::table(
            'budget_limits', function (Blueprint $table) {
            $table->dropColumn('repeat_freq');
        }
        );
        Schema::table(
            'budget_limits', function (Blueprint $table) {
            $table->string('repeat_freq', 20)->default('monthly');
        }
        );

        // put data back:
        $this->restoreRepeatFreqsToVarchar($backup);


        // create it again, correctly.
        Schema::table(
            'budget_limits', function (Blueprint $table) {
            $table->unique(['budget_id', 'startdate', 'repeat_freq'], 'unique_limit');
        }
        );

        // create NEW table for transactions <> budgets
        Schema::create(
            'budget_transaction', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('budget_id')->unsigned();
            $table->integer('transaction_id')->unsigned();
            $table->foreign('budget_id')->references('id')->on('budgets')->onDelete('cascade');
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');
            $table->unique(['budget_id', 'transaction_id'], 'budid_tid_unique');
        }
        );

        // create NEW table for transactions <> categories
        Schema::create(
            'category_transaction', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('category_id')->unsigned();
            $table->integer('transaction_id')->unsigned();
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');
            $table->unique(['category_id', 'transaction_id'], 'catid_tid_unique');
        }
        );
    }

    /**
     * @return array
     */
    private function backupRepeatFreqsFromEnum(): array
    {
        $backup = [];
        $set    = BudgetLimit::get();
        /** @var BudgetLimit $entry */
        foreach ($set as $entry) {
            $backup[$entry->id] = $entry->repeat_freq;
        }

        return $backup;
    }

    /**
     * Same routine.
     *
     * @return array
     */
    private function backupRepeatFreqsFromString()
    {
        return $this->backupRepeatFreqsFromEnum();
    }

    /**
     * @param array $backup
     *
     * @return bool
     */
    private function restoreRepeatFreqsToEnum(array $backup): bool
    {
        foreach ($backup as $id => $repeatFreq) {
            $budgetLimit = BudgetLimit::find($id);
            if (!in_array($repeatFreq, ['daily', 'weekly', 'monthly', 'quarterly', 'half-year', 'yearly'])) {
                $repeatFreq = 'monthly';
            }
            $budgetLimit->repeat_freq = $repeatFreq;
            $budgetLimit->save();
        }

        return true;
    }

    /**
     * @param array $backup
     *
     * @return bool
     */
    private function restoreRepeatFreqsToVarchar(array $backup): bool
    {
        foreach ($backup as $id => $repeatFreq) {
            $budgetLimit              = BudgetLimit::find($id);
            $budgetLimit->repeat_freq = $repeatFreq;
            $budgetLimit->save();
        }

        return true;
    }
}
