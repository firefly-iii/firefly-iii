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
    }

    public function migrate()
    {

        // create the accounts:
        $this->_createAccounts();
    }

    protected function _createAccounts()
    {

        $accounts = App::make('Firefly\Storage\Account\AccountRepositoryInterface');

        foreach ($this->JSON->accounts as $entry) {
            // create account:
            if ($entry->openingbalance == 0) {
                $account = $accounts->store(['name' => $entry->name]);
            } else {
                $account = $accounts->storeWithInitialBalance(
                    ['name' => $entry->name],
                    new Carbon($entry->openingbalancedate),
                    floatval($entry->openingbalance)
                );
            }
            if ($account) {
                $this->map['accounts'][$entry->id] = $account->id;
            }
        }
    }
}