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

use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\AccountMetaFactory;
use FireflyIII\Factory\TagFactory;
use FireflyIII\Models\Account;
use FireflyIII\Models\Note;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Rules\UniqueIban;
use FireflyIII\Support\NullArrayObject;
use Illuminate\Support\Facades\Log;

/**
 * Trait JournalServiceTrait
 */
trait JournalServiceTrait
{
    private AccountRepositoryInterface  $accountRepository;
    private BudgetRepositoryInterface   $budgetRepository;
    private CategoryRepositoryInterface $categoryRepository;
    private TagFactory                  $tagFactory;

    /**
     * @throws FireflyException
     */
    protected function getAccount(string $transactionType, string $direction, array $data): ?Account
    {
        // some debug logging:
        Log::debug(sprintf('Now in getAccount(%s)', $direction), $data);

        // expected type of source account, in order of preference
        /** @var array $array */
        $array         = config('firefly.expected_source_types');
        $expectedTypes = $array[$direction];
        unset($array);

        // and now try to find it, based on the type of transaction.
        $message       = 'Transaction = %s, %s account should be in: %s. Direction is %s.';
        Log::debug(sprintf($message, $transactionType, $direction, implode(', ', $expectedTypes[$transactionType] ?? ['UNKNOWN']), $direction));

        $result        = $this->findAccountById($data, $expectedTypes[$transactionType]);
        $result        = $this->findAccountByIban($result, $data, $expectedTypes[$transactionType]);
        $ibanResult    = $result;
        $result        = $this->findAccountByNumber($result, $data, $expectedTypes[$transactionType]);
        $numberResult  = $result;
        $result        = $this->findAccountByName($result, $data, $expectedTypes[$transactionType]);
        $nameResult    = $result;

        // if $result (find by name) is NULL, but IBAN is set, any result of the search by NAME can't overrule
        // this account. In such a case, the name search must be retried with a new name.
        if (null !== $nameResult && null === $numberResult && null === $ibanResult && '' !== (string) $data['iban'] && '' !== (string) $nameResult->iban) {
            $data['name'] = sprintf('%s (%s)', $data['name'], $data['iban']);
            Log::debug(sprintf('Search again using the new name, "%s".', $data['name']));
            $result       = $this->findAccountByName(null, $data, $expectedTypes[$transactionType]);
        }

        // the account that Firefly III creates must be "creatable", aka select the one we can create from the list just in case
        $creatableType = $this->getCreatableType($expectedTypes[$transactionType]);

        // if the result is NULL but the ID is set, an account could exist of the wrong type.
        // that data can be used to create a new account of the right type.
        if (null === $result && null !== $data['id'] && null !== $creatableType) {
            Log::debug(sprintf('Account #%d may exist and be of the wrong type, use data to create one of the right type.', $data['id']));
            $temp = $this->findAccountById(['id' => $data['id']], []);
            if (null !== $temp) {
                $tempData = [
                    'name'   => $temp->name,
                    'iban'   => $temp->iban,
                    'number' => null,
                    'bic'    => null,
                ];
                $result   = $this->createAccount(null, $tempData, $creatableType);
            }
        }
        if (null === $result && null !== $creatableType) {
            Log::debug('If nothing is found, create it.');
            $result = $this->createAccount($result, $data, $creatableType);
        }
        if (null === $result) {
            Log::debug('If cant be created, return cash account.');
            $result = $this->getCashAccount($result, $data, $expectedTypes[$transactionType]);
        }

        return $result;
    }

    private function findAccountById(array $data, array $types): ?Account
    {
        // first attempt, find by ID.
        if (null !== $data['id']) {
            $search = $this->accountRepository->find((int) $data['id']);
            if (null !== $search && in_array($search->accountType->type, $types, true)) {
                Log::debug(
                    sprintf('Found "account_id" object: #%d, "%s" of type %s (1)', $search->id, $search->name, $search->accountType->type)
                );

                return $search;
            }
            if (null !== $search && 0 === count($types)) {
                Log::debug(
                    sprintf('Found "account_id" object: #%d, "%s" of type %s (2)', $search->id, $search->name, $search->accountType->type)
                );

                return $search;
            }
        }
        Log::debug(sprintf('Found no account by ID #%d of types', $data['id']), $types);

        return null;
    }

    private function findAccountByIban(?Account $account, array $data, array $types): ?Account
    {
        if (null !== $account) {
            Log::debug(sprintf('Already have account #%d ("%s"), return that.', $account->id, $account->name));

            return $account;
        }
        if (null === $data['iban'] || '' === $data['iban']) {
            Log::debug('IBAN is empty, will not search for IBAN.');

            return null;
        }
        // find by preferred type.
        $source = $this->accountRepository->findByIbanNull($data['iban'], [$types[0]]);
        // or any expected type.
        $source ??= $this->accountRepository->findByIbanNull($data['iban'], $types);

        if (null !== $source) {
            Log::debug(sprintf('Found "account_iban" object: #%d, %s', $source->id, $source->name));

            return $source;
        }
        Log::debug(sprintf('Found no account with IBAN "%s" of expected types', $data['iban']), $types);

        return null;
    }

    private function findAccountByNumber(?Account $account, array $data, array $types): ?Account
    {
        if (null !== $account) {
            Log::debug(sprintf('Already have account #%d ("%s"), return that.', $account->id, $account->name));

            return $account;
        }
        if (null === $data['number'] || '' === $data['number']) {
            Log::debug('Account number is empty, will not search for account number.');

            return null;
        }
        // find by preferred type.
        $source = $this->accountRepository->findByAccountNumber((string) $data['number'], [$types[0]]);

        // or any expected type.
        $source ??= $this->accountRepository->findByAccountNumber((string) $data['number'], $types);

        if (null !== $source) {
            Log::debug(sprintf('Found account: #%d, %s', $source->id, $source->name));

            return $source;
        }

        Log::debug(sprintf('Found no account with account number "%s" of expected types', $data['number']), $types);

        return null;
    }

    private function findAccountByName(?Account $account, array $data, array $types): ?Account
    {
        if (null !== $account) {
            Log::debug(sprintf('Already have account #%d ("%s"), return that.', $account->id, $account->name));

            return $account;
        }
        if (null === $data['name'] || '' === $data['name']) {
            Log::debug('Account name is empty, will not search for account name.');

            return null;
        }

        // find by preferred type.
        $source = $this->accountRepository->findByName($data['name'], [$types[0]]);

        // or any expected type.
        $source ??= $this->accountRepository->findByName($data['name'], $types);

        if (null !== $source) {
            Log::debug(sprintf('Found "account_name" object: #%d, %s', $source->id, $source->name));

            return $source;
        }
        Log::debug(sprintf('Found no account with account name "%s" of expected types', $data['name']), $types);

        return null;
    }

    private function getCreatableType(array $types): ?string
    {
        $result = null;
        $list   = config('firefly.dynamic_creation_allowed');

        /** @var string $type */
        foreach ($types as $type) {
            if (true === in_array($type, $list, true)) {
                $result = $type;

                break;
            }
        }

        return $result;
    }

    /**
     * @throws FireflyException
     */
    private function createAccount(?Account $account, array $data, string $preferredType): ?Account
    {
        Log::debug('Now in createAccount()', $data);
        // return new account.
        if (null !== $account) {
            Log::debug(
                sprintf(
                    'Was given %s account #%d ("%s") so will simply return that.',
                    $account->accountType->type,
                    $account->id,
                    $account->name
                )
            );
        }
        if (null === $account) {
            // final attempt, create it.
            if (AccountTypeEnum::ASSET->value === $preferredType) {
                throw new FireflyException(sprintf('TransactionFactory: Cannot create asset account with these values: %s', json_encode($data)));
            }
            // fix name of account if only IBAN is given:
            if ('' === (string) $data['name'] && '' !== (string) $data['iban']) {
                Log::debug(sprintf('Account name is now IBAN ("%s")', $data['iban']));
                $data['name'] = $data['iban'];
            }
            // fix name of account if only number is given:
            if ('' === (string) $data['name'] && '' !== (string) $data['number']) {
                Log::debug(sprintf('Account name is now account number ("%s")', $data['number']));
                $data['name'] = $data['number'];
            }
            // if name is still NULL, return NULL.
            if ('' === (string) $data['name']) {
                Log::debug('Account name is still NULL, return NULL.');

                return null;
            }
            // 2025-04-19 sanity check on IBAN.
            $validator = new UniqueIban(null, $preferredType);
            if('' !== (string) $data['iban'] && !$validator->passes('iban', $data['iban'])) {
                Log::warning(sprintf('IBAN "%s" is already in use, quietly ignore it.', $data['iban']));
                $data['iban'] = null;
            }



            // $data['name'] = $data['name'] ?? '(no name)';

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

    private function getCashAccount(?Account $account, array $data, array $types): ?Account
    {
        // return cash account.
        if (null === $account && '' === (string) $data['name']
            && in_array(AccountTypeEnum::CASH->value, $types, true)) {
            $account = $this->accountRepository->getCashAccount();
        }
        Log::debug('Cannot return cash account, return input instead.');

        return $account;
    }

    /**
     * @throws FireflyException
     */
    protected function getAmount(string $amount): string
    {
        if ('' === $amount) {
            throw new FireflyException(sprintf('The amount cannot be an empty string: "%s"', $amount));
        }
        Log::debug(sprintf('Now in getAmount("%s")', $amount));
        if (0 === bccomp('0', $amount)) {
            throw new FireflyException(sprintf('The amount seems to be zero: "%s"', $amount));
        }

        return $amount;
    }

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

    protected function storeBudget(TransactionJournal $journal, NullArrayObject $data): void
    {
        if (TransactionTypeEnum::WITHDRAWAL->value !== $journal->transactionType->type) {
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

    protected function storeNotes(TransactionJournal $journal, ?string $notes): void
    {
        $notes = (string) $notes;
        $note  = $journal->notes()->first();
        if ('' !== $notes) {
            if (null === $note) {
                $note = new Note();
                $note->noteable()->associate($journal);
            }
            $note->text = $notes;
            $note->save();
            Log::debug(sprintf('Stored notes for journal #%d', $journal->id));

            return;
        }
        // try to delete existing notes.
        $note?->delete();
    }

    /**
     * Link tags to journal.
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
            $string = (string) $string;
            Log::debug(sprintf('Now at tag "%s"', $string));
            if ('' !== $string) {
                $tag = $this->tagFactory->findOrCreate($string);
                if (null !== $tag) {
                    $set[] = $tag->id;
                }
            }
        }
        $set = array_unique($set);
        Log::debug('End of loop.');
        Log::debug(sprintf('Total nr. of tags: %d', count($tags)), $tags);
        $journal->tags()->sync($set);
    }
}
