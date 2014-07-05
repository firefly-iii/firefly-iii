<?php
/**
 * Created by PhpStorm.
 * User: sander
 * Date: 03/07/14
 * Time: 21:34
 */

namespace Firefly\Helper\Migration;


class MigrationHelper implements MigrationHelperInterface
{
    protected $path;
    protected $JSON;
    protected $map = [];

    public function loadFile($path)
    {
        $this->path = $path;
    }

    public function validFile()
    {
        // file does not exist:
        if (!file_exists($this->path)) {
            \Log::error('Migration file ' . $this->path . ' does not exist!');
            return false;
        }

        // load the content:
        $content = file_get_contents($this->path);
        if ($content === false) {
            return false;
        }

        // parse the content
        $this->JSON = json_decode($content);
        if (is_null($this->JSON)) {
            return false;
        }
        \Log::info('Migration file ' . $this->path . ' is valid!');
        return true;
    }

    public function migrate()
    {
        \Log::info('Start of migration.');
        \DB::beginTransaction();

        try {
            $this->_importAccounts();
            $this->_importComponents();
            $this->_importPiggybanks();


        } catch (\Firefly\Exception\FireflyException $e) {
            \DB::rollBack();
            \Log::error('Rollback because of error!');
            \Log::error($e->getMessage());
            return false;
        }

        \DB::commit();
        \Log::info('Done!');
        return true;
    }

    protected function _importAccounts()
    {

        /** @var \Firefly\Storage\Account\AccountRepositoryInterface $accounts */
        $accounts = \App::make('Firefly\Storage\Account\AccountRepositoryInterface');
        \Log::info('Going to import ' . count($this->JSON->accounts) . ' accounts.');
        foreach ($this->JSON->accounts as $entry) {
            // create account:
            if ($entry->openingbalance == 0) {
                $account = $accounts->store(['name' => $entry->name]);
            } else {
                $account = $accounts->storeWithInitialBalance(
                    ['name' => $entry->name],
                    new \Carbon\Carbon($entry->openingbalancedate),
                    floatval($entry->openingbalance)
                );
            }
            $this->map['accounts'][$entry->id] = $account->id;
            \Log::info('Imported account "' . $entry->name . '" with balance ' . $entry->openingbalance);
        }
    }

    protected function _importComponents()
    {
        $beneficiaryAT = \AccountType::where('description', 'Beneficiary account')->first();
        $budgetType = \ComponentType::where('type', 'budget')->first();
        $categoryType = \ComponentType::where('type', 'category')->first();
        foreach ($this->JSON->components as $entry) {
            switch ($entry->type->type) {
                case 'beneficiary':
                    $beneficiary = $this->_importBeneficiary($entry, $beneficiaryAT);
                    $this->map['accounts'][$entry->id] = $beneficiary->id;
                    break;
                case 'category':
                    $component = $this->_importComponent($entry, $categoryType);
                    $this->map['components'][$entry->id] = $component->id;
                    break;
                case 'budget':
                    $component = $this->_importComponent($entry, $budgetType);
                    $this->map['components'][$entry->id] = $component->id;
                    break;
            }

        }
    }

    protected function _importPiggybanks() {

        /** @var \Firefly\Storage\Account\AccountRepositoryInterface $accounts */
        $accounts = \App::make('Firefly\Storage\Account\AccountRepositoryInterface');

        // get type for piggy:
        $piggyAT = \AccountType::where('description', 'Piggy bank')->first();
        foreach($this->JSON->piggybanks as $piggyBank) {
        }
    }

    protected function _importBeneficiary($component, \AccountType $beneficiaryAT)
    {
        /** @var \Firefly\Storage\Account\AccountRepositoryInterface $accounts */
        $accounts = \App::make('Firefly\Storage\Account\AccountRepositoryInterface');
        return $accounts->store(
            [
                'name'         => $component->name,
                'account_type' => $beneficiaryAT
            ]
        );
    }

    protected function _importComponent($component, \ComponentType $type)
    {
        /** @var \Firefly\Storage\Component\ComponentRepositoryInterface $components */
        $components = \App::make('Firefly\Storage\Component\ComponentRepositoryInterface');
        return $components->store(['name' => $component->name, 'component_type' => $type]);
    }
}