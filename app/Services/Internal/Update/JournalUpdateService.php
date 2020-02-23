<?php
/**
 * JournalUpdateService.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Services\Internal\Update;

use Carbon\Carbon;
use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\TagFactory;
use FireflyIII\Factory\TransactionJournalMetaFactory;
use FireflyIII\Factory\TransactionTypeFactory;
use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Services\Internal\Support\JournalServiceTrait;
use FireflyIII\Support\NullArrayObject;
use FireflyIII\Validation\AccountValidator;
use Log;

/**
 * Class to centralise code that updates a journal given the input by system.
 *
 * Class JournalUpdateService
 * TODO test me
 */
class JournalUpdateService
{
    use JournalServiceTrait;

    /** @var BillRepositoryInterface */
    private $billRepository;
    /** @var CurrencyRepositoryInterface */
    private $currencyRepository;
    /** @var array The data to update the journal with. */
    private $data;
    /** @var Account The destination account. */
    private $destinationAccount;
    /** @var Transaction */
    private $destinationTransaction;
    /** @var array All meta values that are dates. */
    private $metaDate;
    /** @var array All meta values that are strings. */
    private $metaString;
    /** @var Account Source account of the journal */
    private $sourceAccount;
    /** @var Transaction Source transaction of the journal. */
    private $sourceTransaction;
    /** @var TransactionGroup The parent group. */
    private $transactionGroup;
    /** @var TransactionJournal The journal to update. */
    private $transactionJournal;
    /** @var Account If new account info is submitted, this array will hold the valid destination. */
    private $validDestination;
    /** @var Account If new account info is submitted, this array will hold the valid source. */
    private $validSource;

    /**
     * JournalUpdateService constructor.
     */
    public function __construct()
    {
        $this->billRepository     = app(BillRepositoryInterface::class);
        $this->categoryRepository = app(CategoryRepositoryInterface::class);
        $this->budgetRepository   = app(BudgetRepositoryInterface::class);
        $this->tagFactory         = app(TagFactory::class);
        $this->accountRepository  = app(AccountRepositoryInterface::class);
        $this->currencyRepository = app(CurrencyRepositoryInterface::class);
        $this->metaString         = ['sepa_cc', 'sepa_ct_op', 'sepa_ct_id', 'sepa_db', 'sepa_country', 'sepa_ep', 'sepa_ci', 'sepa_batch_id', 'recurrence_id',
                                     'internal_reference', 'bunq_payment_id', 'external_id',];
        $this->metaDate           = ['interest_date', 'book_date', 'process_date', 'due_date', 'payment_date', 'invoice_date',];
    }

    /**
     * @param array $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * @param TransactionGroup $transactionGroup
     */
    public function setTransactionGroup(TransactionGroup $transactionGroup): void
    {
        $this->transactionGroup = $transactionGroup;
        $this->billRepository->setUser($transactionGroup->user);
        $this->categoryRepository->setUser($transactionGroup->user);
        $this->budgetRepository->setUser($transactionGroup->user);
        $this->tagFactory->setUser($transactionGroup->user);
        $this->accountRepository->setUser($transactionGroup->user);
    }

    /**
     * @param TransactionJournal $transactionJournal
     */
    public function setTransactionJournal(TransactionJournal $transactionJournal): void
    {
        $this->transactionJournal = $transactionJournal;
    }

    /**
     *
     */
    public function update(): void
    {
        Log::debug(sprintf('Now in JournalUpdateService for journal #%d.', $this->transactionJournal->id));
        // can we update account data using the new type?
        if ($this->hasValidAccounts()) {
            Log::info('-- account info is valid, now update.');
            // update accounts:
            $this->updateAccounts();

            // then also update transaction journal type ID:
            $this->updateType();
            $this->transactionJournal->refresh();
        }
        // find and update bill, if possible.
        $this->updateBill();

        // update journal fields.
        $this->updateField('description');
        $this->updateField('date');
        $this->updateField('order');

        $this->transactionJournal->save();
        $this->transactionJournal->refresh();

        // update category
        if ($this->hasFields(['category_id', 'category_name'])) {
            Log::debug('Will update category.');

            $this->storeCategory($this->transactionJournal, new NullArrayObject($this->data));
        }
        // update budget
        if ($this->hasFields(['budget_id', 'budget_name'])) {
            Log::debug('Will update budget.');
            $this->storeBudget($this->transactionJournal, new NullArrayObject($this->data));
        }
        // update tags

        if ($this->hasFields(['tags'])) {
            Log::debug('Will update tags.');
            $tags = $this->data['tags'] ?? null;
            $this->storeTags($this->transactionJournal, $tags);
        }

        // update notes.
        if ($this->hasFields(['notes'])) {
            $notes = '' === (string)$this->data['notes'] ? null : $this->data['notes'];
            $this->storeNotes($this->transactionJournal, $notes);
        }
        // update meta fields.
        // first string
        if ($this->hasFields($this->metaString)) {
            Log::debug('Meta string fields are present.');
            $this->updateMetaFields();
        }

        // then date fields.
        if ($this->hasFields($this->metaDate)) {
            Log::debug('Meta date fields are present.');
            $this->updateMetaDateFields();
        }


        // update transactions.
        if ($this->hasFields(['currency_id', 'currency_code'])) {
            $this->updateCurrency();
        }
        if ($this->hasFields(['amount'])) {
            $this->updateAmount();
        }

        // amount, foreign currency.
        if ($this->hasFields(['foreign_currency_id', 'foreign_currency_code', 'foreign_amount'])) {
            $this->updateForeignAmount();
        }

        // TODO update hash

        app('preferences')->mark();

        $this->transactionJournal->refresh();
    }

    /**
     * Get destination transaction.
     *
     * @return Transaction
     */
    private function getDestinationTransaction(): Transaction
    {
        if (null === $this->destinationTransaction) {
            $this->destinationTransaction = $this->transactionJournal->transactions()->where('amount', '>', 0)->first();
        }

        return $this->destinationTransaction;
    }

    /**
     * This method returns the current or expected type of the journal (in case of a change) based on the data in the array.
     *
     * If the array contains key 'type' and the value is correct, this is returned. Otherwise, the original type is returned.
     *
     * @return string
     */
    private function getExpectedType(): string
    {
        Log::debug('Now in getExpectedType()');
        if ($this->hasFields(['type'])) {
            return ucfirst('opening-balance' === $this->data['type'] ? 'opening balance' : $this->data['type']);
        }

        return $this->transactionJournal->transactionType->type;
    }

    /**
     * @return Account
     */
    private function getOriginalDestinationAccount(): Account
    {
        if (null === $this->destinationAccount) {
            $destination              = $this->getSourceTransaction();
            $this->destinationAccount = $destination->account;
        }

        return $this->destinationAccount;
    }

    /**
     * @return Account
     */
    private function getOriginalSourceAccount(): Account
    {
        if (null === $this->sourceAccount) {
            $source              = $this->getSourceTransaction();
            $this->sourceAccount = $source->account;
        }

        return $this->sourceAccount;
    }

    /**
     * @return Transaction
     */
    private function getSourceTransaction(): Transaction
    {
        if (null === $this->sourceTransaction) {
            $this->sourceTransaction = $this->transactionJournal->transactions()->with(['account'])->where('amount', '<', 0)->first();
        }
        Log::debug(sprintf('getSourceTransaction: %s', $this->sourceTransaction->amount));

        return $this->sourceTransaction;
    }

    /**
     * Does a validation and returns the destination account. This method will break if the dest isn't really valid.
     *
     * @return Account
     */
    private function getValidDestinationAccount(): Account
    {
        Log::debug('Now in getValidDestinationAccount().');

        if (!$this->hasFields(['destination_id', 'destination_name'])) {
            return $this->getOriginalDestinationAccount();
        }

        $destInfo = [
            'id'     => (int)($this->data['destination_id'] ?? null),
            'name'   => $this->data['destination_name'] ?? null,
            'iban'   => $this->data['destination_iban'] ?? null,
            'number' => $this->data['destination_number'] ?? null,
            'bic'    => $this->data['destination_bic'] ?? null,
        ];

        // make new account validator.
        $expectedType = $this->getExpectedType();
        Log::debug(sprintf('Expected type (new or unchanged) is %s', $expectedType));
        try {
            $result = $this->getAccount($expectedType, 'destination', $destInfo);
        } catch (FireflyException $e) {
            Log::error(sprintf('getValidDestinationAccount() threw unexpected error: %s', $e->getMessage()));
            $result = $this->getOriginalDestinationAccount();
        }

        return $result;
    }

    /**
     * Does a validation and returns the source account. This method will break if the source isn't really valid.
     *
     * @return Account
     */
    private function getValidSourceAccount(): Account
    {
        Log::debug('Now in getValidSourceAccount().');

        if (!$this->hasFields(['source_id', 'source_name'])) {
            return $this->getOriginalSourceAccount();
        }

        $sourceInfo = [
            'id'     => (int)($this->data['source_id'] ?? null),
            'name'   => $this->data['source_name'] ?? null,
            'iban'   => $this->data['source_iban'] ?? null,
            'number' => $this->data['source_number'] ?? null,
            'bic'    => $this->data['source_bic'] ?? null,
        ];

        $expectedType = $this->getExpectedType();
        try {
            $result = $this->getAccount($expectedType, 'source', $sourceInfo);
        } catch (FireflyException $e) {
            Log::error(sprintf('Cant get the valid source account: %s', $e->getMessage()));

            $result = $this->getOriginalSourceAccount();
        }

        Log::debug(sprintf('getValidSourceAccount() will return #%d ("%s")', $result->id, $result->name));

        return $result;
    }

    /**
     * @param array $fields
     *
     * @return bool
     */
    private function hasFields(array $fields): bool
    {
        foreach ($fields as $field) {
            if (array_key_exists($field, $this->data)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    private function hasValidAccounts(): bool
    {
        return $this->hasValidSourceAccount() && $this->hasValidDestinationAccount();
    }

    /**
     * @return bool
     */
    private function hasValidDestinationAccount(): bool
    {
        Log::debug('Now in hasValidDestinationAccount().');
        $destId   = $this->data['destination_id'] ?? null;
        $destName = $this->data['destination_name'] ?? null;

        if (!$this->hasFields(['destination_id', 'destination_name'])) {
            $destination = $this->getOriginalDestinationAccount();
            $destId      = $destination->id;
            $destName    = $destination->name;
        }

        // make new account validator.
        $expectedType = $this->getExpectedType();
        Log::debug(sprintf('Expected type (new or unchanged) is %s', $expectedType));

        // make a new validator.
        /** @var AccountValidator $validator */
        $validator = app(AccountValidator::class);
        $validator->setTransactionType($expectedType);
        $validator->setUser($this->transactionJournal->user);
        $validator->source = $this->getValidSourceAccount();


        $result = $validator->validateDestination($destId, $destName);
        Log::debug(sprintf('hasValidDestinationAccount(%d, "%s") will return %s', $destId, $destName, var_export($result, true)));

        // validate submitted info:
        return $result;
    }

    /**
     * @return bool
     */
    private function hasValidSourceAccount(): bool
    {
        Log::debug('Now in hasValidSourceAccount().');
        $sourceId   = $this->data['source_id'] ?? null;
        $sourceName = $this->data['source_name'] ?? null;

        if (!$this->hasFields(['source_id', 'source_name'])) {
            $sourceAccount = $this->getOriginalSourceAccount();
            $sourceId      = $sourceAccount->id;
            $sourceName    = $sourceAccount->name;
        }

        // make new account validator.
        $expectedType = $this->getExpectedType();
        Log::debug(sprintf('Expected type (new or unchanged) is %s', $expectedType));

        // make a new validator.
        /** @var AccountValidator $validator */
        $validator = app(AccountValidator::class);
        $validator->setTransactionType($expectedType);
        $validator->setUser($this->transactionJournal->user);

        $result = $validator->validateSource($sourceId, $sourceName);
        Log::debug(sprintf('hasValidSourceAccount(%d, "%s") will return %s', $sourceId, $sourceName, var_export($result, true)));

        // validate submitted info:
        return $result;
    }

    /**
     * Will update the source and destination accounts of this journal. Assumes they are valid.
     */
    private function updateAccounts(): void
    {
        $source      = $this->getValidSourceAccount();
        $destination = $this->getValidDestinationAccount();

        // cowardly refuse to update if both accounts are the same.
        if ($source->id === $destination->id) {
            Log::error(sprintf('Source + dest accounts are equal (%d, "%s")', $source->id, $source->name));

            return;
        }

        $sourceTransaction = $this->getSourceTransaction();
        $sourceTransaction->account()->associate($source);
        $sourceTransaction->save();

        $destTransaction = $this->getDestinationTransaction();
        $destTransaction->account()->associate($destination);
        $destTransaction->save();

        // refresh transactions.
        $this->sourceTransaction->refresh();
        $this->destinationTransaction->refresh();


        Log::debug(sprintf('Will set source to #%d ("%s")', $source->id, $source->name));
        Log::debug(sprintf('Will set dest to #%d ("%s")', $destination->id, $destination->name));
    }

    /**
     *
     */
    private function updateAmount(): void
    {
        $value = $this->data['amount'] ?? '';
        try {
            $amount = $this->getAmount($value);
        } catch (FireflyException $e) {
            Log::debug(sprintf('getAmount("%s") returns error: %s', $value, $e->getMessage()));

            return;
        }
        $sourceTransaction         = $this->getSourceTransaction();
        $sourceTransaction->amount = app('steam')->negative($amount);
        $sourceTransaction->save();


        $destTransaction         = $this->getDestinationTransaction();
        $destTransaction->amount = app('steam')->positive($amount);
        $destTransaction->save();


        // refresh transactions.
        $this->sourceTransaction->refresh();
        $this->destinationTransaction->refresh();
        Log::debug(sprintf('Updated amount to "%s"', $amount));
    }

    /**
     * Update journal bill information.
     */
    private function updateBill(): void
    {
        $type = $this->transactionJournal->transactionType->type;
        if ((
                array_key_exists('bill_id', $this->data)
                || array_key_exists('bill_name', $this->data)
            )
            && TransactionType::WITHDRAWAL === $type
        ) {
            $billId                            = (int)($this->data['bill_id'] ?? 0);
            $billName                          = (string)($this->data['bill_name'] ?? '');
            $bill                              = $this->billRepository->findBill($billId, $billName);
            $this->transactionJournal->bill_id = null === $bill ? null : $bill->id;
            Log::debug('Updated bill ID');
        }
    }

    /**
     *
     */
    private function updateCurrency(): void
    {
        $currencyId   = $this->data['currency_id'] ?? null;
        $currencyCode = $this->data['currency_code'] ?? null;
        $currency     = $this->currencyRepository->findCurrency($currencyId, $currencyCode);
        if (null !== $currency) {
            // update currency everywhere.
            $this->transactionJournal->transaction_currency_id = $currency->id;
            $this->transactionJournal->save();

            $source                          = $this->getSourceTransaction();
            $source->transaction_currency_id = $currency->id;
            $source->save();

            $dest                          = $this->getDestinationTransaction();
            $dest->transaction_currency_id = $currency->id;
            $dest->save();

            // refresh transactions.
            $this->sourceTransaction->refresh();
            $this->destinationTransaction->refresh();
            Log::debug(sprintf('Updated currency to #%d (%s)', $currency->id, $currency->code));
        }
    }

    /**
     * Update journal generic field. Cannot be set to NULL.
     *
     * @param $fieldName
     */
    private function updateField($fieldName): void
    {
        if (array_key_exists($fieldName, $this->data) && '' !== (string)$this->data[$fieldName]) {
            $this->transactionJournal->$fieldName = $this->data[$fieldName];
            Log::debug(sprintf('Updated %s', $fieldName));
        }
    }


    /**
     *
     */
    private function updateForeignAmount(): void
    {
        $amount          = $this->data['foreign_amount'] ?? null;
        $foreignAmount   = $this->getForeignAmount($amount);
        $source          = $this->getSourceTransaction();
        $dest            = $this->getDestinationTransaction();
        $foreignCurrency = $source->foreignCurrency;

        // find currency in data array
        $newForeignId    = $this->data['foreign_currency_id'] ?? null;
        $newForeignCode  = $this->data['foreign_currency_code'] ?? null;
        $foreignCurrency = $this->currencyRepository->findCurrencyNull($newForeignId, $newForeignCode) ?? $foreignCurrency;

        // not the same as normal currency
        if (null !== $foreignCurrency && $foreignCurrency->id === $this->transactionJournal->transaction_currency_id) {
            Log::error(sprintf('Foreign currency is equal to normal currency (%s)', $foreignCurrency->code));

            return;
        }

        // add foreign currency info to source and destination if possible.
        if (null !== $foreignCurrency && null !== $foreignAmount) {
            $source->foreign_currency_id = $foreignCurrency->id;
            $source->foreign_amount      = app('steam')->negative($foreignAmount);
            $source->save();


            $dest->foreign_currency_id = $foreignCurrency->id;
            $dest->foreign_amount      = app('steam')->positive($foreignAmount);
            $dest->save();

            Log::debug(sprintf('Update foreign info to %s (#%d) %s', $foreignCurrency->code, $foreignCurrency->id, $foreignAmount));

            // refresh transactions.
            $this->sourceTransaction->refresh();
            $this->destinationTransaction->refresh();

            return;
        }
        if ('0' === $amount) {
            $source->foreign_currency_id = null;
            $source->foreign_amount      = null;
            $source->save();

            $dest->foreign_currency_id = null;
            $dest->foreign_amount      = null;
            $dest->save();
            Log::debug(sprintf('Foreign amount is "%s" so remove foreign amount info.', $amount));
        }
        Log::info('Not enough info to update foreign currency info.');

        // refresh transactions.
        $this->sourceTransaction->refresh();
        $this->destinationTransaction->refresh();
    }

    /**
     *
     */
    private function updateMetaDateFields(): void
    {
        /** @var TransactionJournalMetaFactory $factory */
        $factory = app(TransactionJournalMetaFactory::class);

        foreach ($this->metaDate as $field) {
            if ($this->hasFields([$field])) {
                try {
                    $value = '' === (string)$this->data[$field] ? null : new Carbon($this->data[$field]);
                } catch (Exception $e) {
                    Log::debug(sprintf('%s is not a valid date value: %s', $this->data[$field], $e->getMessage()));

                    return;
                }
                Log::debug(sprintf('Field "%s" is present ("%s"), try to update it.', $field, $value));
                $set = [
                    'journal' => $this->transactionJournal,
                    'name'    => $field,
                    'data'    => $value,
                ];
                $factory->updateOrCreate($set);
            }
        }
    }

    /**
     *
     */
    private function updateMetaFields(): void
    {
        /** @var TransactionJournalMetaFactory $factory */
        $factory = app(TransactionJournalMetaFactory::class);

        foreach ($this->metaString as $field) {
            if ($this->hasFields([$field])) {
                $value = '' === $this->data[$field] ? null : $this->data[$field];
                Log::debug(sprintf('Field "%s" is present ("%s"), try to update it.', $field, $value));
                $set = [
                    'journal' => $this->transactionJournal,
                    'name'    => $field,
                    'data'    => $value,
                ];
                $factory->updateOrCreate($set);
            }
        }
    }

    /**
     * Updates journal transaction type.
     */
    private function updateType(): void
    {
        Log::debug('Now in updateType()');
        if ($this->hasFields(['type'])) {
            $type = 'opening-balance' === $this->data['type'] ? 'opening balance' : $this->data['type'];
            Log::debug(
                sprintf(
                    'Trying to change journal #%d from a %s to a %s.',
                    $this->transactionJournal->id, $this->transactionJournal->transactionType->type, $type
                )
            );

            /** @var TransactionTypeFactory $typeFactory */
            $typeFactory = app(TransactionTypeFactory::class);
            $result      = $typeFactory->find($this->data['type']);
            if (null !== $result) {
                Log::debug('Changed transaction type!');
                $this->transactionJournal->transaction_type_id = $result->id;
                $this->transactionJournal->save();

                return;
            }

            return;
        }
        Log::debug('No type field present.');
    }
}
