<?php
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

/**
 * Class DatabaseSeeder
 */
class DatabaseSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $this->call('AccountTypeSeeder');
        $this->call('TransactionCurrencySeeder');
        $this->call('TransactionTypeSeeder');
        $this->call('PermissionSeeder');

        // set up basic test data (as little as possible):
        if (App::environment() == 'testing' || App::environment() == 'local') {
            $this->call('TestDataSeeder');
        }
        // set up basic test data (as little as possible):
        if (App::environment() == 'split') {
            $this->call('SplitDataSeeder');
        }
    }

}
