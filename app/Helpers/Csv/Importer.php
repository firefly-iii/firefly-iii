<?php

namespace FireflyIII\Helpers\Csv;

use Auth;
use Config;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Csv\Converter\ConverterInterface;
use FireflyIII\Helpers\Csv\PostProcessing\PostProcessorInterface;
use FireflyIII\Helpers\Csv\Specifix\SpecifixInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use Log;

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
    /** @var  array */
    protected $importData;
    /** @var  array */
    protected $importRow;
    /** @var int */
    protected $imported = 0;
    /** @var array */
    protected $map;
    /** @var  array */
    protected $mapped;
    /** @var  array */
    protected $roles;
    /** @var  int */
    protected $rows = 0;
    /** @var array */
    protected $specifix = [];

    /** @var  Collection */
    protected $journals;

    /**
     * Used by CsvController.
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Used by CsvController
     *
     * @return int
     */
    public function getImported()
    {
        return $this->imported;
    }

    /**
     * Used by CsvController
     *
     * @return int
     */
    public function getRows()
    {
        return $this->rows;
    }

    /**
     * @return Collection
     */
    public function getJournals()
    {
        return $this->journals;
    }


    /**
     * @throws FireflyException
     */
    public function run()
    {
        set_time_limit(0);

        $this->journals = new Collection;
        $this->map      = $this->data->getMap();
        $this->roles    = $this->data->getRoles();
        $this->mapped   = $this->data->getMapped();
        $this->specifix = $this->data->getSpecifix();

        foreach ($this->data->getReader() as $index => $row) {
            if ($this->parseRow($index)) {
                Log::debug('--- Importing row ' . $index);
                $this->rows++;
                $result = $this->importRow($row);
                if (!($result instanceof TransactionJournal)) {
                    Log::error('Caught error at row #' . $index . ': ' . $result);
                    $this->errors[$index] = $result;
                } else {
                    $this->imported++;
                    $this->journals->push($result);
                }
                Log::debug('---');
            }
        }
    }

    /**
     * @param int $index
     *
     * @return bool
     */
    protected function parseRow($index)
    {
        return (($this->data->hasHeaders() && $index > 1) || !$this->data->hasHeaders());
    }

    /**
     * @param $row
     *
     * @throws FireflyException
     * @return string|bool
     */
    protected function importRow($row)
    {

        $data = $this->getFiller(); // These fields are necessary to create a new transaction journal. Some are optional
        foreach ($row as $index => $value) {
            $role  = isset($this->roles[$index]) ? $this->roles[$index] : '_ignore';
            $class = Config::get('csv.roles.' . $role . '.converter');
            $field = Config::get('csv.roles.' . $role . '.field');

            Log::debug('Column #' . $index . ' (role: ' . $role . ') : converter ' . $class . ' stores its data into field ' . $field . ':');

            /** @var ConverterInterface $converter */
            $converter = app('FireflyIII\Helpers\Csv\Converter\\' . $class);
            $converter->setData($data); // the complete array so far.
            $converter->setField($field);
            $converter->setIndex($index);
            $converter->setMapped($this->mapped);
            $converter->setValue($value);
            $converter->setRole($role);
            $data[$field] = $converter->convert();
        }
        // move to class vars.
        $this->importData = $data;
        $this->importRow  = $row;
        unset($data, $row);
        // post processing and validating.
        $this->postProcess();
        $result = $this->validateData();

        if (!($result === true)) {
            return $result; // return error.
        }
        $journal = $this->createTransactionJournal();

        return $journal;
    }

    /**
     * @return array
     */
    protected function getFiller()
    {
        $filler = [];
        foreach (Config::get('csv.roles') as $role) {
            if (isset($role['field'])) {
                $fieldName          = $role['field'];
                $filler[$fieldName] = null;
            }
        }
        // some extra's:
        $filler['bill-id']                 = null;
        $filler['opposing-account-object'] = null;
        $filler['asset-account-object']    = null;
        $filler['amount-modifier']         = '1';

        return $filler;

    }

    /**
     * Row denotes the original data.
     *
     * @return void
     */
    protected function postProcess()
    {
        // do bank specific fixes (must be enabled but now all of them.

        foreach ($this->getSpecifix() as $className) {
            /** @var SpecifixInterface $specifix */
            $specifix = app('FireflyIII\Helpers\Csv\Specifix\\' . $className);
            $specifix->setData($this->importData);
            $specifix->setRow($this->importRow);
            Log::debug('Now post-process specifix named ' . $className . ':');
            $this->importData = $specifix->fix();
        }


        $set = Config::get('csv.post_processors');
        foreach ($set as $className) {
            /** @var PostProcessorInterface $postProcessor */
            $postProcessor = app('FireflyIII\Helpers\Csv\PostProcessing\\' . $className);
            $postProcessor->setData($this->importData);
            Log::debug('Now post-process processor named ' . $className . ':');
            $this->importData = $postProcessor->process();
        }

    }

    /**
     * @return array
     */
    public function getSpecifix()
    {
        return is_array($this->specifix) ? $this->specifix : [];
    }

    /**
     *
     * @return bool|string
     */
    protected function validateData()
    {
        if (is_null($this->importData['date']) && is_null($this->importData['date-rent'])) {
            return 'No date value for this row.';
        }
        if (is_null($this->importData['opposing-account-object'])) {
            return 'Opposing account is null';
        }

        if (!($this->importData['asset-account-object'] instanceof Account)) {
            return 'No asset account to import into.';
        }

        return true;
    }

    /**
     *
     * @return TransactionJournal|string
     */
    protected function createTransactionJournal()
    {
        bcscale(2);
        $date = $this->importData['date'];
        if (is_null($this->importData['date'])) {
            $date = $this->importData['date-rent'];
        }


        $transactionType = $this->getTransactionType(); // defaults to deposit
        $errors          = new MessageBag;
        $journal         = TransactionJournal::create(
            ['user_id'     => Auth::user()->id, 'transaction_type_id' => $transactionType->id, 'transaction_currency_id' => $this->importData['currency']->id,
             'description' => $this->importData['description'], 'completed' => 0, 'date' => $date, 'bill_id' => $this->importData['bill-id'],]
        );
        if ($journal->getErrors()->count() == 0) {
            // first transaction
            $accountId   = $this->importData['asset-account-object']->id; // create first transaction:
            $amount      = $this->importData['amount'];
            $transaction = Transaction::create(['transaction_journal_id' => $journal->id, 'account_id' => $accountId, 'amount' => $amount]);
            $errors      = $transaction->getErrors();

            // second transaction
            $accountId   = $this->importData['opposing-account-object']->id; // create second transaction:
            $amount      = bcmul($this->importData['amount'], -1);
            $transaction = Transaction::create(['transaction_journal_id' => $journal->id, 'account_id' => $accountId, 'amount' => $amount]);
            $errors      = $transaction->getErrors()->merge($errors);
        }
        if ($errors->count() == 0) {
            $journal->completed = 1;
            $journal->save();
        } else {
            $text = join(',', $errors->all());

            return $text;
        }
        $this->saveBudget($journal);
        $this->saveCategory($journal);
        $this->saveTags($journal);

        // some debug info:
        $journalId = $journal->id;
        $type      = $journal->transactionType->type;
        /** @var Account $asset */
        $asset = $this->importData['asset-account-object'];
        /** @var Account $opposing */
        $opposing = $this->importData['opposing-account-object'];

        Log::info('Created journal #' . $journalId . ' of type ' . $type . '!');
        Log::info('Asset account ' . $asset->name . ' (#' . $asset->id . ') lost/gained: ' . $this->importData['amount']);
        Log::info($opposing->accountType->type . ' ' . $opposing->name . ' (#' . $opposing->id . ') lost/gained: ' . bcmul($this->importData['amount'], -1));

        return $journal;
    }

    /**
     * @return TransactionType
     */
    protected function getTransactionType()
    {
        $transactionType = TransactionType::where('type', 'Deposit')->first();
        if ($this->importData['amount'] < 0) {
            $transactionType = TransactionType::where('type', 'Withdrawal')->first();
        }

        if (in_array($this->importData['opposing-account-object']->accountType->type, ['Asset account', 'Default account'])) {
            $transactionType = TransactionType::where('type', 'Transfer')->first();
        }

        return $transactionType;
    }

    /**
     * @param TransactionJournal $journal
     */
    protected function saveBudget(TransactionJournal $journal)
    {
        // add budget:
        if (!is_null($this->importData['budget'])) {
            $journal->budgets()->save($this->importData['budget']);
        }
    }

    /**
     * @param TransactionJournal $journal
     */
    protected function saveCategory(TransactionJournal $journal)
    {
        // add category:
        if (!is_null($this->importData['category'])) {
            $journal->categories()->save($this->importData['category']);
        }
    }

    /**
     * @param TransactionJournal $journal
     */
    protected function saveTags(TransactionJournal $journal)
    {
        if (!is_null($this->importData['tags'])) {
            foreach ($this->importData['tags'] as $tag) {
                $journal->tags()->save($tag);
            }
        }
    }

    /**
     * @param Data $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

}
