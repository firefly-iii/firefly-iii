<?php
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
class DatabaseSeeder extends Seeder {

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

        if (App::environment() == 'testing' || App::environment() == 'homestead') {
            $this->call('TestDataSeeder');
        }
    }

}
