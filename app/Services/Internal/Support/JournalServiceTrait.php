<?php
/**
 * JournalServiceTrait.php
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

namespace FireflyIII\Services\Internal\Support;

use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\AccountMetaFactory;
use FireflyIII\Factory\TagFactory;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Note;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Support\NullArrayObject;
use Log;

/**
 * Trait JournalServiceTrait
 *
 */
trait JournalServiceTrait
{
    private AccountRepositoryInterface  $accountRepository;
    private BudgetRepositoryInterface   $budgetRepository;
    private CategoryRepositoryInterface $categoryRepository;
    private TagFactory                  $tagFactory;

    /**
     * @param string $transactionType
     * @param string $direction
     * @param array  $data
     *
     * @return Account
     * @codeCoverageIgnore
     * @throws FireflyException
     */
    protected function getAccount(string $transactionType, string $direction, array $data): Account
    {
        // some debug logging:
        Log::debug(sprintf('Now in getAccount(%s)', $direction), $data);

        // final result:
        $result = null;

        // expected type of source account, in order of preference
        /** @var array $array */
        $array         = config('firefly.expected_source_types');
        $expectedTypes = $array[$direction];
        unset($array);

        // and now try to find it, based on the type of transaction.
        $message = 'Based on the fact that the transaction is a %s, the %s account should be in: %s. Direction is %s.';
        Log::debug(sprintf($message, $transactionType, $direction, implode(', ', $expectedTypes[$transactionType]), $direction));

        $result = $this->findAccountById($data, $expectedTypes[$transactionType]);
        $result = $this->findAccountByName($result, $data, $expectedTypes[$transactionType]);
        $result = $this->findAccountByIban($result, $data, $expectedTypes[$transactionType]);
        $result = $this->createAccount($result, $data, $expectedTypes[$transactionType][0]);

        return $this->getCashAccount($result, $data, $expectedTypes[$transactionType]);
    }

    /**
     * @param array $data
     * @param array $types
     *
     * @return Account|null
     */
    private function findAccountById(array $data, array $types): ?Account
    {
        $search = null;
        // first attempt, find by ID.
        if (null !== $data['id']) {
            $search = $this->accountRepository->findNull($data['id']);
            if (null !== $search && in_array($search->accountType->type, $types, true)) {
                Log::debug(
                    sprintf('Found "account_id" object: #%d, "%s" of type %s', $search->id, $search->name, $search->accountType->type)
                );
            }
        }

        return $search;
    }

    /**
     * @param Account|null $account
     * @param array        $data
     * @param array        $types
     *
     * @return Account|null
     */
    private function findAccountByName(?Account $account, array $data, array $types): ?Account
    {
        // second attempt, find by name.
        if (null === $account && null !== $data['name']) {
            Log::debug('Found nothing by account ID.');
            // find by preferred type.
            $source = $this->accountRepository->findByName($data['name'], [$types[0]]);
            // or any expected type.
            $source = $source ?? $this->accountRepository->findByName($data['name'], $types);

            if (null !== $source) {
                Log::debug(sprintf('Found "account_name" object: #%d, %s', $source->id, $source->name));

                $account = $source;
            }
        }

        return $account;
    }

    /**
     * @param Account|null $account
     * @param array        $data
     * @param array        $types
     *
     * @return Account|null
     */
    private function findAccountByIban(?Account $account, array $data, array $types): ?Account
    {
        // third attempt, find by IBAN
        if (null === $account && null !== $data['iban']) {
            Log::debug(sprintf('Found nothing by account iban "%s".', $data['iban']));
            // find by preferred type.
            $source = $this->accountRepository->findByIbanNull($data['iban'], [$types[0]]);
            // or any expected type.
            $source = $source ?? $this->accountRepository->findByIbanNull($data['iban'], $types);

            if (null !== $source) {
                Log::debug(sprintf('Found "account_iban" object: #%d, %s', $source->id, $source->name));

                $account = $source;
            }
        }

        return $account;
    }

    /**
     * @param Account|null $account
     * @param array        $data
     * @param string       $preferredType
     *
     * @return Account
     * @throws FireflyException
     */
    private function createAccount(?Account $account, array $data, string $preferredType): Account
    {
        Log::debug('Now in createAccount()', $data);
        // return new account.
        if (null !== $account) {
            Log::debug(
                sprintf(
                    'Was also given %s account #%d ("%s") so will simply return that.',
                    $account->accountType->type, $account->id, $account->name

                )
            );
        }
        if (null === $account) {

            // final attempt, create it.
            if (AccountType::ASSET === $preferredType) {
                throw new FireflyException(sprintf('TransactionFactory: Cannot create asset account with these values: %s',json_encode($data)));
            }
            // fix name of account if only IBAN is given:
            if ('' === (string)$data['name'] && '' !== (string)$data['iban']) {
                Log::debug(sprintf('Account name is now IBAN ("%s")', $data['iban']));
                $data['name'] = $data['iban'];
            }

            $data['name'] = $data['name'] ?? '(no name)';

            $account = $this->accountRepository->store(
                [
                    'account_type_id'   => null,
                    'account_type_name' => $preferredType,
                    'name'              => $data['name'],
                    'virtual_balance'   => null,
                    'active'            => true,
                    'iban'              => $data['iban'],
                    'currency_id'       => $data['currency_id'] ?? null,
                    'order'             => $this->accountRepository->maxOrder($preferredType),
                ]
            );
            // store BIC
            if (null !== $data['bic']) {
                /** @var AccountMetaFactory $metaFactory */
                $metaFactory = app(AccountMetaFactory::class);
                $metaFactory->create(['account_id' => $account->id, 'name' => 'BIC', 'data' => $data['bic']]);
            }
            // store account number
            if (null !== $data['number']) {
                /** @var AccountMetaFactory $metaFactory */
                $metaFactory = app(AccountMetaFactory::class);
                $metaFactory->create(['account_id' => $account->id, 'name' => 'account_number', 'data' => $data['number']]);
            }

        }

        return $account;
    }

    /**
     * @param Account|null $account
     * @param array        $data
     * @param array        $types
     *
     * @return Account|null
     */
    private function getCashAccount(?Account $account, array $data, array $types): ?Account
    {
        // return cash account.
        if (null === $account && null === $data['name']
            && in_array(AccountType::CASH, $types, true)) {
            $account = $this->accountRepository->getCashAccount();
        }

        return $account;
    }

    /**
     * @param string $amount
     *
     * @return string
     * @throws FireflyException
     * @codeCoverageIgnore
     */
    protected function getAmount(string $amount): string
    {
        if ('' === $amount) {
            throw new FireflyException(sprintf('The amount cannot be an empty string: "%s"', $amount));
        }
        if (0 === bccomp('0', $amount)) {
            throw new FireflyException(sprintf('The amount seems to be zero: "%s"', $amount));
        }

        return $amount;
    }

    /**
     * @param string|null $amount
     *
     * @return string
     * @codeCoverageIgnore
     */
    protected function getForeignAmount(?string $amount): ?string
    {
        if (null === $amount) {
            Log::debug('No foreign amount info in array. Return NULL');

            return null;
        }
        if ('' === $amount) {
            Log::debug('Foreign amount is empty string, return NULL.');

            return null;
        }
        if (0 === bccomp('0', $amount)) {
            Log::debug('Foreign amount is 0.0, return NULL.');

            return null;
        }
        Log::debug(sprintf('Foreign amount is %s', $amount));

        return $amount;
    }

    /**
     * @param TransactionJournal $journal
     * @param NullArrayObject    $data
     *
     * @codeCoverageIgnore
     */
    protected function storeBudget(TransactionJournal $journal, NullArrayObject $data): void
    {
        if (TransactionType::WITHDRAWAL !== $journal->transactionType->type) {
            $journal->budgets()->sync([]);

            return;
        }
        $budget = $this->budgetRepository->findBudget($data['budget_id'], $data['budget_name']);
        if (null !== $budget) {
            Log::debug(sprintf('Link budget #%d to journal #%d', $budget->id, $journal->id));
            $journal->budgets()->sync([$budget->id]);

            return;
        }
        // if the budget is NULL, sync empty.
        $journal->budgets()->sync([]);
    }

    /**
     * @param TransactionJournal $journal
     * @param NullArrayObject    $data
     *
     * @codeCoverageIgnore
     */
    protected function storeCategory(TransactionJournal $journal, NullArrayObject $data): void
    {
        $category = $this->categoryRepository->findCategory($data['category_id'], $data['category_name']);
        if (null !== $category) {
            Log::debug(sprintf('Link category #%d to journal #%d', $category->id, $journal->id));
            $journal->categories()->sync([$category->id]);

            return;
        }
        // if the category is NULL, sync empty.
        $journal->categories()->sync([]);
    }

    /**
     * @param TransactionJournal $journal
     * @param string             $notes
     *
     * @codeCoverageIgnore
     */
    protected function storeNotes(TransactionJournal $journal, ?string $notes): void
    {
        $notes = (string)$notes;
        $note  = $journal->notes()->first();
        if ('' !== $notes) {
            if (null === $note) {
                $note = new Note;
                $note->noteable()->associate($journal);
            }
            $note->text = $notes;
            $note->save();
            Log::debug(sprintf('Stored notes for journal #%d', $journal->id));

            return;
        }
        if ('' === $notes && null !== $note) {
            // try to delete existing notes.
            try {
                $note->delete();
            } catch (Exception $e) { // @phpstan-ignore-line
                // @ignoreException
            }
        }
    }

    /**
     * Link tags to journal.
     *
     * @param TransactionJournal $journal
     * @param array              $tags
     *
     * @codeCoverageIgnore
     */
    protected function storeTags(TransactionJournal $journal, ?array $tags): void
    {
        Log::debug('Now in storeTags()', $tags ?? []);
        $this->tagFactory->setUser($journal->user);
        $set = [];
        if (!is_array($tags)) {
            Log::debug('Tags is not an array, break.');

            return;
        }
        Log::debug('Start of loop.');
        foreach ($tags as $string) {
            $string = (string)$string;
            Log::debug(sprintf('Now at tag "%s"', $string));
            if ('' !== $string) {
                $tag = $this->tagFactory->findOrCreate($string);
                if (null !== $tag) {
                    $set[] = $tag->id;
                }
            }
        }
        Log::debug('End of loop.');
        Log::debug(sprintf('Total nr. of tags: %d', count($tags)), $tags);
        $journal->tags()->sync($set);
        Log::debug('Done!');
    }
}
