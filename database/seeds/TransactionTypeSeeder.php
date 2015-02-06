<?php
use Illuminate\Database\Seeder;
use FireflyIII\Models\TransactionType;
/**
 * Class TransactionTypeSeeder
 */
class TransactionTypeSeeder extends Seeder
{
    public function run()
    {

        DB::table('transaction_types')->delete();

        TransactionType::create(['type' => 'Withdrawal']);
        TransactionType::create(['type' => 'Deposit']);
        TransactionType::create(['type' => 'Transfer']);
        TransactionType::create(['type' => 'Opening balance']);
    }

} 
