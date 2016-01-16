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
        if (App::environment() == 'testing' && gethostname() != 'lightning') {
            $this->call('TestDataSeeder');
        }

        // this one is reserved for more extensive testing.
        if (App::environment() == 'testing' || gethostname() == 'lightning') {
            $this->call('VisualTestDataSeeder');
        }
    }

}
