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

        if (App::environment() == 'testing') {
            $this->call('TestDataSeeder');
        }
    }

}
