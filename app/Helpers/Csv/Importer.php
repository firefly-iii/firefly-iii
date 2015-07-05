<?php

namespace FireflyIII\Helpers\Csv;

use App;
use Auth;
use Carbon\Carbon;
use Config;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Csv\Converter\ConverterInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use Illuminate\Support\MessageBag;
use Log;

set_time_limit(0);

/**
 * Class Importer
 *
 * @package FireflyIII\Helpers\Csv
 */
class Importer
{

    /** @var Data */
    protected $data;
    /** @var array */
    protected $errors;
    /** @var array */
    protected $map;
    /** @var  array */
    protected $mapped;
    /** @var  array */
    protected $roles;

    /**
     * @param $value
     */
    public function parseRaboDebetCredit($value)
    {
        if ($value == 'D') {
            return -1;
        }

        return 1;
    }

    /**
     *
     */
    public function run()
    {
        $this->map    = $this->data->getMap();
        $this->roles  = $this->data->getRoles();
        $this->mapped = $this->data->getMapped();
        foreach ($this->data->getReader() as $index => $row) {
            $result = $this->importRow($row);
            if (!($result === true)) {
                $this->errors[$index] = $result;
                Log::error('ImportRow: ' . $result);
            }
        }

        return count($this->errors);
    }

    /**
     * @param $row
     *
     * @throws FireflyException
     * @return string|bool
     */
    protected function importRow($row)
    {
        /*
         * These fields are necessary to create a new transaction journal. Some are optional:
         */
        $data = $this->getFiller();
        foreach ($row as $index => $value) {
            $role  = isset($this->roles[$index]) ? $this->roles[$index] : '_ignore';
            $class = Config::get('csv.roles.' . $role . '.converter');
            $field = Config::get('csv.roles.' . $role . '.field');

            if (is_null($class)) {
                throw new FireflyException('No converter for field of type "' . $role . '".');
            }
            if (is_null($field)) {
                throw new FireflyException('No place to store value of type "' . $role . '".');
            }
            /** @var ConverterInterface $converter */
            $converter = App::make('FireflyIII\Helpers\Csv\Converter\\' . $class);
            $converter->setData($data); // the complete array so far.
            $converter->setField($field);
            $converter->setIndex($index);
            $converter->setValue($value);
            $converter->setRole($role);
            //            if (is_array($field)) {
            //                $convertResult = $converter->convert();
            //                foreach ($field as $fieldName) {
            //                    $data[$fieldName] = $convertResult[$fieldName];
            //                }
            //            } else {
            $data[$field] = $converter->convert();
            //            }

        }
        $data   = $this->postProcess($data, $row);
        $result = $this->validateData($data);
        if ($result === true) {
            $result = $this->createTransactionJournal($data);
        } else {
            Log::error('Validator: ' . $result);
        }
        if ($result instanceof TransactionJournal) {
            return true;
        }

        return 'Not a journal.';

    }

    /**
     * @return array
     */
    protected function getFiller()
    {
        return [
            'description'             => '',
            'asset-account'           => null,
            'opposing-account'        => '',
            'opposing-account-object' => null,
            'date'                    => null,
            'currency'                => null,
            'amount'                  => null,
            'amount-modifier'         => 1,
            'ignored'                 => null,
            'date-rent'               => null,
        ];

    }

    /**
     * Row denotes the original data.
     *
     * @param array $data
     * @param array $row
     *
     * @return array
     */
    protected function postProcess(array $data, array $row)
    {
        bcscale(2);
        $data['description'] = trim($data['description']);
        $data['amount']      = bcmul($data['amount'], $data['amount-modifier']);
        if ($data['amount'] < 0) {
            // create expense account:
            $accountType = AccountType::where('type', 'Expense account')->first();
        } else {
            // create revenue account:
            $accountType = AccountType::where('type', 'Revenue account')->first();
        }

        // do bank specific fixes:

        $specifix = new Specifix();
        $specifix->setData($data);
        $specifix->setRow($row);
        $specifix->fix($data, $row);

        // get data back:
        $data = $specifix->getData();

        $data['opposing-account-object'] = Account::firstOrCreateEncrypted(
            [
                'user_id'         => Auth::user()->id,
                'name'            => ucwords($data['opposing-account']),
                'account_type_id' => $accountType->id,
                'active'          => 1,
            ]
        );

        return $data;
    }

    /**
     * @param $data
     *
     * @return bool|string
     */
    protected function validateData($data)
    {
        if (is_null($data['date']) && is_null($data['date-rent'])) {
            return 'No date value for this row.';
        }
        if (strlen($data['description']) == 0) {
            return 'No valid description';
        }
        if (is_null($data['opposing-account-object'])) {
            return 'Opposing account is null';
        }

        return true;
    }

    /**
     * @param array $data
     *
     * @return static
     */
    protected function createTransactionJournal(array $data)
    {
        bcscale(2);
        $date = $data['date'];
        if (is_null($data['date'])) {
            $date = $data['date-rent'];
        }
        if ($data['amount'] < 0) {
            $transactionType = TransactionType::where('type', 'Withdrawal')->first();
        } else {
            $transactionType = TransactionType::where('type', 'Deposit')->first();
        }
        $errors  = new MessageBag;
        $journal = TransactionJournal::create(
            [
                'user_id'                 => Auth::user()->id,
                'transaction_type_id'     => $transactionType->id,
                'bill_id'                 => null,
                'transaction_currency_id' => $data['currency']->id,
                'description'             => $data['description'],
                'completed'               => 0,
                'date'                    => $date,
            ]
        );
        $errors  = $journal->getErrors()->merge($errors);
        if ($journal->getErrors()->count() == 0) {
            // create both transactions:
            $transaction = Transaction::create(
                [
                    'transaction_journal_id' => $journal->id,
                    'account_id'             => $data['asset-account']->id,
                    'amount'                 => $data['amount']
                ]
            );
            $errors      = $transaction->getErrors()->merge($errors);

            $transaction = Transaction::create(
                [
                    'transaction_journal_id' => $journal->id,
                    'account_id'             => $data['opposing-account-object']->id,
                    'amount'                 => bcmul($data['amount'], -1)
                ]
            );
            $errors      = $transaction->getErrors()->merge($errors);
        }
        if ($errors->count() == 0) {
            $journal->completed = 1;
            $journal->save();
        }

        return $journal;


    }

    /**
     * @param Data $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @param $value
     *
     * @return Carbon
     */
    protected function parseDate($value)
    {
        return Carbon::createFromFormat($this->data->getDateFormat(), $value);
    }

}