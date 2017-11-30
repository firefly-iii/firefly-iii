<?php
/**
 * ImportStorage.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Import\Storage;

use ErrorException;
use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Import\Object\ImportJournal;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\Note;
use FireflyIII\Models\TransactionType;
use Illuminate\Support\Collection;
use Log;

/**
 * Is capable of storing individual ImportJournal objects.
 * Class ImportStorage.
 */
class ImportStorage
{
    use ImportSupport;

    /** @var Collection */
    public $errors;
    /** @var Collection */
    public $journals;
    /** @var int */
    protected $defaultCurrencyId = 1; // yes, hard coded
    /** @var ImportJob */
    protected $job;
    /** @var Collection */
    protected $rules;
    /** @var string */
    private $dateFormat = 'Ymd';
    /** @var Collection */
    private $objects;
    /** @var array */
    private $transfers = [];

    /**
     * ImportStorage constructor.
     */
    public function __construct()
    {
        $this->objects  = new Collection;
        $this->journals = new Collection;
        $this->errors   = new Collection;
    }

    /**
     * @param string $dateFormat
     */
    public function setDateFormat(string $dateFormat)
    {
        $this->dateFormat = $dateFormat;
    }

    /**
     * @param ImportJob $job
     */
    public function setJob(ImportJob $job)
    {
        $this->job               = $job;
        $currency                = app('amount')->getDefaultCurrencyByUser($this->job->user);
        $this->defaultCurrencyId = $currency->id;
        $this->transfers         = $this->getTransfers();
        $this->rules             = $this->getRules();
    }

    /**
     * @param Collection $objects
     */
    public function setObjects(Collection $objects)
    {
        $this->objects = $objects;
    }

    /**
     * Do storage of import objects. Is the main function.
     *
     * @return bool
     */
    public function store(): bool
    {
        $this->objects->each(
            function (ImportJournal $importJournal, int $index) {
                try {
                    $this->storeImportJournal($index, $importJournal);
                } catch (FireflyException | ErrorException | Exception $e) {
                    $this->errors->push($e->getMessage());
                    Log::error(sprintf('Cannot import row #%d because: %s', $index, $e->getMessage()));
                }
            }
        );
        Log::info('ImportStorage has finished.');

        return true;
    }

    /**
     * @param int           $index
     * @param ImportJournal $importJournal
     *
     * @return bool
     *
     * @throws FireflyException
     */
    protected function storeImportJournal(int $index, ImportJournal $importJournal): bool
    {
        Log::debug(sprintf('Going to store object #%d with description "%s"', $index, $importJournal->getDescription()));
        $assetAccount      = $importJournal->asset->getAccount();
        $amount            = $importJournal->getAmount();
        $currencyId        = $this->getCurrencyId($importJournal);
        $foreignCurrencyId = $this->getForeignCurrencyId($importJournal, $currencyId);
        $date              = $importJournal->getDate($this->dateFormat)->format('Y-m-d');
        $opposingAccount   = $this->getOpposingAccount($importJournal->opposing, $assetAccount->id, $amount);
        $transactionType   = $this->getTransactionType($amount, $opposingAccount);
        $description       = $importJournal->getDescription();

        // First step done!
        $this->job->addStepsDone(1);

        /**
         * Check for double transfer.
         */
        $parameters = [
            'type'        => $transactionType,
            'description' => $description,
            'amount'      => $amount,
            'date'        => $date,
            'asset'       => $assetAccount->name,
            'opposing'    => $opposingAccount->name,
        ];
        if ($this->isDoubleTransfer($parameters) || $this->hashAlreadyImported($importJournal->hash)) {
            $this->job->addStepsDone(3);
            // throw error
            $message = sprintf('Detected a possible duplicate, skip this one (hash: %s).', $importJournal->hash);
            Log::error($message, $parameters);
            throw new FireflyException($message);
        }
        unset($parameters);

        // store journal and create transactions:
        $parameters = [
            'type'             => $transactionType,
            'currency'         => $currencyId,
            'foreign_currency' => $foreignCurrencyId,
            'asset'            => $assetAccount,
            'opposing'         => $opposingAccount,
            'description'      => $description,
            'date'             => $date,
            'hash'             => $importJournal->hash,
            'amount'           => $amount,
        ];
        $journal    = $this->storeJournal($parameters);
        unset($parameters);

        // Another step done!
        $this->job->addStepsDone(1);

        // store meta object things:
        $this->storeCategory($journal, $importJournal->category->getCategory());
        $this->storeBudget($journal, $importJournal->budget->getBudget());

        // to save bill, also give it the amount:
        $importJournal->bill->setAmount($amount);

        $this->storeBill($journal, $importJournal->bill->getBill());
        $this->storeMeta($journal, $importJournal->metaDates);
        $this->storeTags($importJournal->tags, $journal);

        // set notes for journal:
        $dbNote = new Note();
        $dbNote->noteable()->associate($journal);
        $dbNote->text = trim($importJournal->notes);
        $dbNote->save();

        // set journal completed:
        $journal->completed = true;
        $journal->save();

        // Another step done!
        $this->job->addStepsDone(1);

        // run rules:
        $this->applyRules($journal);
        // Another step done!
        $this->job->addStepsDone(1);
        $this->journals->push($journal);

        Log::info(sprintf('Imported new journal #%d: "%s", amount %s %s.', $journal->id, $journal->description, $journal->transactionCurrency->code, $amount));

        return true;
    }

    /**
     * @param array $parameters
     *
     * @return bool
     */
    private function isDoubleTransfer(array $parameters): bool
    {
        Log::debug('Check if is a double transfer.');
        if (TransactionType::TRANSFER !== $parameters['type']) {
            Log::debug(sprintf('Is a %s, not a transfer so no.', $parameters['type']));

            return false;
        }

        $amount   = app('steam')->positive($parameters['amount']);
        $names    = [$parameters['asset'], $parameters['opposing']];
        $transfer = [];

        sort($names);

        foreach ($this->transfers as $transfer) {
            $hits = 0;
            if ($parameters['description'] === $transfer['description']) {
                ++$hits;
                Log::debug(sprintf('Description "%s" equals "%s", hits = %d', $parameters['description'], $transfer['description'], $hits));
            }
            if ($names === $transfer['names']) {
                ++$hits;
                Log::debug(sprintf('Involved accounts, "%s" equals "%s", hits = %d', join(',', $names), join(',', $transfer['names']), $hits));
            }
            if (0 === bccomp($amount, $transfer['amount'])) {
                ++$hits;
                Log::debug(sprintf('Amount %s equals %s, hits = %d', $amount, $transfer['amount'], $hits));
            }
            if ($parameters['date'] === $transfer['date']) {
                ++$hits;
                Log::debug(sprintf('Date %s equals %s, hits = %d', $parameters['date'], $transfer['date'], $hits));
            }
            // number of hits is 4? Then it's a match
            if (4 === $hits) {
                Log::error(
                    'There already is a transfer imported with these properties. Compare existing with new. ',
                    ['existing' => $transfer, 'new' => $parameters]
                );

                return true;
            }
        }

        return false;
    }
}
