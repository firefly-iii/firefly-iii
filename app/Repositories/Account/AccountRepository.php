<?php
/**
 * AccountRepository.php
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

namespace FireflyIII\Repositories\Account;

use Carbon\Carbon;
use DB;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Category;
use FireflyIII\Models\Tag;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\User;
use Log;
use Validator;

/**
 * Class AccountRepository.
 */
class AccountRepository implements AccountRepositoryInterface
{

    /** @var User */
    private $user;

    use FindAccountsTrait;
    /** @var array */
    private $validFields = ['accountRole', 'ccMonthlyPaymentDate', 'ccType', 'accountNumber', 'currency_id', 'BIC'];

    /**
     * Moved here from account CRUD.
     *
     * @param array $types
     *
     * @return int
     */
    public function count(array $types): int
    {
        $count = $this->user->accounts()->accountTypeIn($types)->count();

        return $count;
    }

    /**
     * Moved here from account CRUD.
     *
     * @param Account $account
     * @param Account $moveTo
     *
     * @return bool
     */
    public function destroy(Account $account, Account $moveTo): bool
    {
        if (null !== $moveTo->id) {
            DB::table('transactions')->where('account_id', $account->id)->update(['account_id' => $moveTo->id]);
        }
        if (null !== $account) {
            $account->delete();
        }

        return true;
    }

    /**
     * Returns the date of the very last transaction in this account.
     *
     * @param Account $account
     *
     * @return Carbon
     */
    public function newestJournalDate(Account $account): Carbon
    {
        $last = new Carbon;
        $date = $account->transactions()
                        ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                        ->orderBy('transaction_journals.date', 'DESC')
                        ->orderBy('transaction_journals.order', 'ASC')
                        ->orderBy('transaction_journals.id', 'DESC')
                        ->first(['transaction_journals.date']);
        if (null !== $date) {
            $last = new Carbon($date->date);
        }

        return $last;
    }

    /**
     * Returns the date of the very first transaction in this account.
     *
     * @param Account $account
     *
     * @return TransactionJournal
     */
    public function oldestJournal(Account $account): TransactionJournal
    {
        $first = $account->transactions()
                         ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                         ->orderBy('transaction_journals.date', 'ASC')
                         ->orderBy('transaction_journals.order', 'DESC')
                         ->where('transaction_journals.user_id', $this->user->id)
                         ->orderBy('transaction_journals.id', 'ASC')
                         ->first(['transaction_journals.id']);
        if (null !== $first) {
            return TransactionJournal::find(intval($first->id));
        }

        return new TransactionJournal();
    }

    /**
     * Returns the date of the very first transaction in this account.
     *
     * @param Account $account
     *
     * @return Carbon
     */
    public function oldestJournalDate(Account $account): Carbon
    {
        $journal = $this->oldestJournal($account);
        if (null === $journal->id) {
            return new Carbon;
        }

        return $journal->date;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param array $data
     *
     * @return Account
     */
    public function store(array $data): Account
    {
        $newAccount = $this->storeAccount($data);
        $this->updateMetadata($newAccount, $data);

        if ($this->validOpeningBalanceData($data)) {
            $this->updateInitialBalance($newAccount, $data);

            return $newAccount;
        }
        $this->deleteInitialBalance($newAccount);

        return $newAccount;
    }

    /**
     * @param Account $account
     * @param array   $data
     *
     * @return Account
     */
    public function update(Account $account, array $data): Account
    {
        // update the account:
        $account->name            = $data['name'];
        $account->active          = $data['active'];
        $account->virtual_balance = $data['virtualBalance'];
        $account->iban            = $data['iban'];
        $account->save();

        $this->updateMetadata($account, $data);
        if ($this->validOpeningBalanceData($data)) {
            $this->updateInitialBalance($account, $data);
        }

        return $account;
    }

    /**
     * @param TransactionJournal $journal
     * @param array              $data
     *
     * @return TransactionJournal
     */
    public function updateReconciliation(TransactionJournal $journal, array $data): TransactionJournal
    {
        // update journal
        // update actual journal:
        $data['amount'] = strval($data['amount']);

        // unlink all categories, recreate them:
        $journal->categories()->detach();

        $this->storeCategoryWithJournal($journal, strval($data['category']));

        // update amounts
        /** @var Transaction $transaction */
        foreach ($journal->transactions as $transaction) {
            $transaction->amount = bcmul($data['amount'], '-1');
            if ($transaction->account->accountType->type === AccountType::ASSET) {
                $transaction->amount = $data['amount'];
            }
            $transaction->save();
        }

        $journal->save();

        // update tags:
        if (isset($data['tags']) && is_array($data['tags'])) {
            $this->updateTags($journal, $data['tags']);
        }

        return $journal;
    }

    /**
     * @param Account $account
     */
    protected function deleteInitialBalance(Account $account)
    {
        $journal = $this->openingBalanceTransaction($account);
        if (null !== $journal->id) {
            $journal->delete();
        }
    }

    /**
     * @param Account $account
     *
     * @return TransactionJournal|null
     */
    protected function openingBalanceTransaction(Account $account): TransactionJournal
    {
        $journal = TransactionJournal::leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                                     ->where('transactions.account_id', $account->id)
                                     ->transactionTypes([TransactionType::OPENING_BALANCE])
                                     ->first(['transaction_journals.*']);
        if (null === $journal) {
            Log::debug('Could not find a opening balance journal, return empty one.');

            return new TransactionJournal;
        }
        Log::debug(sprintf('Found opening balance: journal #%d.', $journal->id));

        return $journal;
    }

    /**
     * @param array $data
     *
     * @return Account
     *
     * @throws FireflyException
     */
    protected function storeAccount(array $data): Account
    {
        $data['accountType'] = $data['accountType'] ?? 'invalid';
        $type                = config('firefly.accountTypeByIdentifier.' . $data['accountType']);
        $accountType         = AccountType::whereType($type)->first();
        $data['iban']        = $this->filterIban($data['iban']);
        // verify account type
        if (null === $accountType) {
            throw new FireflyException(sprintf('Account type "%s" is invalid. Cannot create account.', $data['accountType']));
        }

        // account may exist already:
        $existingAccount = $this->findByName($data['name'], [$type]);
        if (null !== $existingAccount->id) {
            Log::warning(sprintf('There already is an account named "%s" of type "%s".', $data['name'], $type));

            return $existingAccount;
        }

        // create it:
        $databaseData
                    = [
            'user_id'         => $this->user->id,
            'account_type_id' => $accountType->id,
            'name'            => $data['name'],
            'virtual_balance' => $data['virtualBalance'],
            'active'          => true === $data['active'] ? true : false,
            'iban'            => $data['iban'],
        ];
        $newAccount = new Account($databaseData);
        Log::debug('Final account creation dataset', $databaseData);
        $newAccount->save();
        // verify its creation:
        if (null === $newAccount->id) {
            Log::error(
                sprintf('Could not create account "%s" (%d error(s))', $data['name'], $newAccount->getErrors()->count()),
                $newAccount->getErrors()->toArray()
            );
            throw new FireflyException(sprintf('Tried to create account named "%s" but failed. The logs have more details.', $data['name']));
        }
        Log::debug(sprintf('Created new account #%d named "%s" of type %s.', $newAccount->id, $newAccount->name, $accountType->type));

        return $newAccount;
    }

    /**
     * @param TransactionJournal $journal
     * @param string             $category
     */
    protected function storeCategoryWithJournal(TransactionJournal $journal, string $category)
    {
        if (strlen($category) > 0) {
            $category = Category::firstOrCreateEncrypted(['name' => $category, 'user_id' => $journal->user_id]);
            $journal->categories()->save($category);
        }
    }

    /**
     * At this point strlen of amount > 0.
     *
     * @param Account $account
     * @param array   $data
     *
     * @return TransactionJournal
     */
    protected function storeInitialBalance(Account $account, array $data): TransactionJournal
    {
        $amount = strval($data['openingBalance']);
        Log::debug(sprintf('Submitted amount is %s', $amount));

        if (0 === bccomp($amount, '0')) {
            return new TransactionJournal;
        }

        $name            = $data['name'];
        $currencyId      = $data['currency_id'];
        $opposing        = $this->storeOpposingAccount($name);
        $transactionType = TransactionType::whereType(TransactionType::OPENING_BALANCE)->first();
        /** @var TransactionJournal $journal */
        $journal = TransactionJournal::create(
            [
                'user_id'                 => $this->user->id,
                'transaction_type_id'     => $transactionType->id,
                'transaction_currency_id' => $currencyId,
                'description'             => 'Initial balance for "' . $account->name . '"',
                'completed'               => true,
                'date'                    => $data['openingBalanceDate'],
            ]
        );
        Log::debug(sprintf('Created new opening balance journal: #%d', $journal->id));

        $firstAccount  = $account;
        $secondAccount = $opposing;
        $firstAmount   = $amount;
        $secondAmount  = bcmul($amount, '-1');
        Log::debug(sprintf('First amount is %s, second amount is %s', $firstAmount, $secondAmount));

        if (bccomp($amount, '0') === -1) {
            Log::debug(sprintf('%s is a negative number.', $amount));
            $firstAccount  = $opposing;
            $secondAccount = $account;
            $firstAmount   = bcmul($amount, '-1');
            $secondAmount  = $amount;
            Log::debug(sprintf('First amount is %s, second amount is %s', $firstAmount, $secondAmount));
        }

        $one = new Transaction(
            [
                'account_id'              => $firstAccount->id,
                'transaction_journal_id'  => $journal->id,
                'amount'                  => $firstAmount,
                'transaction_currency_id' => $currencyId,
            ]
        );
        $one->save(); // first transaction: from

        $two = new Transaction(
            [
                'account_id'              => $secondAccount->id,
                'transaction_journal_id'  => $journal->id,
                'amount'                  => $secondAmount,
                'transaction_currency_id' => $currencyId,]
        );
        $two->save(); // second transaction: to

        Log::debug(sprintf('Stored two transactions, #%d and #%d', $one->id, $two->id));

        return $journal;
    }

    /**
     * @param string $name
     *
     * @return Account
     */
    protected function storeOpposingAccount(string $name): Account
    {
        $opposingData = [
            'accountType'    => 'initial',
            'name'           => $name . ' initial balance',
            'active'         => false,
            'iban'           => '',
            'virtualBalance' => 0,
        ];
        Log::debug('Going to create an opening balance opposing account.');

        return $this->storeAccount($opposingData);
    }

    /**
     * @param Account $account
     * @param array   $data
     *
     * @return bool
     */
    protected function updateInitialBalance(Account $account, array $data): bool
    {
        Log::debug(sprintf('updateInitialBalance() for account #%d', $account->id));
        $openingBalance = $this->openingBalanceTransaction($account);

        // no opening balance journal? create it:
        if (null === $openingBalance->id) {
            Log::debug('No opening balance journal yet, create journal.');
            $this->storeInitialBalance($account, $data);

            return true;
        }
        // opening balance data? update it!
        if (null !== $openingBalance->id) {
            Log::debug('Opening balance journal found, update journal.');
            $this->updateOpeningBalanceJournal($account, $openingBalance, $data);

            return true;
        }

        return true;
    }

    /**
     * @param Account $account
     * @param array   $data
     */
    protected function updateMetadata(Account $account, array $data)
    {
        foreach ($this->validFields as $field) {
            /** @var AccountMeta $entry */
            $entry = $account->accountMeta()->where('name', $field)->first();

            // if $data has field and $entry is null, create new one:
            if (isset($data[$field]) && null === $entry) {
                Log::debug(
                    sprintf(
                        'Created meta-field "%s":"%s" for account #%d ("%s") ',
                        $field,
                        $data[$field],
                        $account->id,
                        $account->name
                    )
                );
                AccountMeta::create(
                    [
                        'account_id' => $account->id,
                        'name'       => $field,
                        'data'       => $data[$field],
                    ]
                );
            }

            // if $data has field and $entry is not null, update $entry:
            if (isset($data[$field]) && null !== $entry) {
                $entry->data = $data[$field];
                $entry->save();
                Log::debug(
                    sprintf(
                        'Updated meta-field "%s":"%s" for account #%d ("%s") ',
                        $field,
                        $data[$field],
                        $account->id,
                        $account->name
                    )
                );
            }
        }
    }

    /**
     * @param Account            $account
     * @param TransactionJournal $journal
     * @param array              $data
     *
     * @return bool
     */
    protected function updateOpeningBalanceJournal(Account $account, TransactionJournal $journal, array $data): bool
    {
        $date       = $data['openingBalanceDate'];
        $amount     = strval($data['openingBalance']);
        $currencyId = intval($data['currency_id']);

        Log::debug(sprintf('Submitted amount for opening balance to update is %s', $amount));

        if (0 === bccomp($amount, '0')) {
            $journal->delete();

            return true;
        }

        // update date:
        $journal->date                    = $date;
        $journal->transaction_currency_id = $currencyId;
        $journal->save();
        // update transactions:
        /** @var Transaction $transaction */
        foreach ($journal->transactions()->get() as $transaction) {
            if ($account->id === $transaction->account_id) {
                Log::debug(sprintf('Will change transaction #%d amount from %s to %s', $transaction->id, $transaction->amount, $amount));
                $transaction->amount                  = $amount;
                $transaction->transaction_currency_id = $currencyId;
                $transaction->save();
            }
            if ($account->id !== $transaction->account_id) {
                $negativeAmount = bcmul($amount, '-1');
                Log::debug(sprintf('Will change transaction #%d amount from %s to %s', $transaction->id, $transaction->amount, $negativeAmount));
                $transaction->amount                  = $negativeAmount;
                $transaction->transaction_currency_id = $currencyId;
                $transaction->save();
            }
        }
        Log::debug('Updated opening balance journal.');

        return true;
    }

    /**
     * @param TransactionJournal $journal
     * @param array              $array
     *
     * @return bool
     */
    protected function updateTags(TransactionJournal $journal, array $array): bool
    {
        // create tag repository
        /** @var TagRepositoryInterface $tagRepository */
        $tagRepository = app(TagRepositoryInterface::class);

        // find or create all tags:
        $tags = [];
        $ids  = [];
        foreach ($array as $name) {
            if (strlen(trim($name)) > 0) {
                $tag    = Tag::firstOrCreateEncrypted(['tag' => $name, 'user_id' => $journal->user_id]);
                $tags[] = $tag;
                $ids[]  = $tag->id;
            }
        }

        // delete all tags connected to journal not in this array:
        if (count($ids) > 0) {
            DB::table('tag_transaction_journal')->where('transaction_journal_id', $journal->id)->whereNotIn('tag_id', $ids)->delete();
        }
        // if count is zero, delete them all:
        if (0 === count($ids)) {
            DB::table('tag_transaction_journal')->where('transaction_journal_id', $journal->id)->delete();
        }

        // connect each tag to journal (if not yet connected):
        /** @var Tag $tag */
        foreach ($tags as $tag) {
            Log::debug(sprintf('Will try to connect tag #%d to journal #%d.', $tag->id, $journal->id));
            $tagRepository->connect($journal, $tag);
        }

        return true;
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    protected function validOpeningBalanceData(array $data): bool
    {
        $data['openingBalance'] = strval($data['openingBalance'] ?? '');
        if (isset($data['openingBalance']) && null !== $data['openingBalance'] && strlen($data['openingBalance']) > 0
            && isset($data['openingBalanceDate'])) {
            Log::debug('Array has valid opening balance data.');

            return true;
        }
        Log::debug('Array does not have valid opening balance data.');

        return false;
    }

    /**
     * @param null|string $iban
     *
     * @return null|string
     */
    private function filterIban(?string $iban)
    {
        if (null === $iban) {
            return null;
        }
        $data      = ['iban' => $iban];
        $rules     = ['iban' => 'required|iban'];
        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            Log::error(sprintf('Detected invalid IBAN ("%s"). Return NULL instead.', $iban));

            return null;
        }

        return $iban;
    }
}
