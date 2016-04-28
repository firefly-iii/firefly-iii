<?php

use FireflyIII\Models\BudgetLimit;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class ChangesForV385
 */
class ChangesForV385 extends Migration
{
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
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
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // remove an index.
        Schema::table(
            'budget_limits', function (Blueprint $table) {
            $table->dropUnique('unique_limit');
            $table->dropForeign('bid_foreign');
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
