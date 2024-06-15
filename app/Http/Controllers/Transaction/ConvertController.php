<?php

/**
 * ConvertController.php
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

namespace FireflyIII\Http\Controllers\Transaction;

use FireflyIII\Events\UpdatedTransactionGroup;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Services\Internal\Update\JournalUpdateService;
use FireflyIII\Support\Http\Controllers\ModelInformation;
use FireflyIII\Transformers\TransactionGroupTransformer;
use FireflyIII\Validation\AccountValidator;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\View\View;

/**
 * Class ConvertController.
 *
 * TODO when converting a split transfer, all sources and destinations must be the same.
 */
class ConvertController extends Controller
{
    use ModelInformation;

    private AccountRepositoryInterface $accountRepository;

    /**
     * ConvertController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // some useful repositories:
        $this->middleware(
            function ($request, $next) {
                $this->accountRepository = app(AccountRepositoryInterface::class);
                app('view')->share('title', (string) trans('firefly.transactions'));
                app('view')->share('mainTitleIcon', 'fa-exchange');

                return $next($request);
            }
        );
    }

    /**
     * Show overview of a to be converted transaction.
     *
     * @return Factory|Redirector|RedirectResponse|View
     *
     * @throws \Exception
     */
    public function index(TransactionType $destinationType, TransactionGroup $group)
    {
        if (!$this->isEditableGroup($group)) {
            return $this->redirectGroupToAccount($group);
        }

        /** @var TransactionGroupTransformer $transformer */
        $transformer = app(TransactionGroupTransformer::class);

        /** @var TransactionJournal $first */
        $first      = $group->transactionJournals()->first();
        $sourceType = $first->transactionType;

        $groupTitle   = $group->title ?? $first->description;
        $groupArray   = $transformer->transformObject($group);
        $subTitle     = (string) trans('firefly.convert_to_' . $destinationType->type, ['description' => $groupTitle]);
        $subTitleIcon = 'fa-exchange';

        // get a list of asset accounts and liabilities and stuff, in various combinations:
        $validDepositSources  = $this->getValidDepositSources();
        $validWithdrawalDests = $this->getValidWithdrawalDests();
        $liabilities          = $this->getLiabilities();
        $assets               = $this->getAssetAccounts();

        // old input variables:
        $preFilled = [
            'source_name' => old('source_name'),
        ];

        if ($sourceType->type === $destinationType->type) { // cannot convert to its own type.
            app('log')->debug('This is already a transaction of the expected type..');
            session()->flash('info', (string) trans('firefly.convert_is_already_type_' . $destinationType->type));

            return redirect(route('transactions.show', [$group->id]));
        }

        return view(
            'transactions.convert',
            compact(
                'sourceType',
                'destinationType',
                'group',
                'groupTitle',
                'groupArray',
                'assets',
                'validDepositSources',
                'liabilities',
                'validWithdrawalDests',
                'preFilled',
                'subTitle',
                'subTitleIcon'
            )
        );
    }

    private function getValidDepositSources(): array
    {
        // make repositories
        $liabilityTypes = [AccountType::MORTGAGE, AccountType::DEBT, AccountType::CREDITCARD, AccountType::LOAN];
        $accountList    = $this->accountRepository
            ->getActiveAccountsByType([AccountType::REVENUE, AccountType::CASH, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE]);
        $grouped        = [];

        // group accounts:
        /** @var Account $account */
        foreach ($accountList as $account) {
            $role = (string) $this->accountRepository->getMetaValue($account, 'account_role');
            $name = $account->name;
            if ('' === $role) {
                $role = 'no_account_type';
            }

            // maybe it's a liability thing:
            if (in_array($account->accountType->type, $liabilityTypes, true)) {
                $role = 'l_' . $account->accountType->type;
            }
            if (AccountType::CASH === $account->accountType->type) {
                $role = 'cash_account';
                $name = sprintf('(%s)', trans('firefly.cash'));
            }
            if (AccountType::REVENUE === $account->accountType->type) {
                $role = 'revenue_account';
            }

            $key                         = (string) trans('firefly.opt_group_' . $role);
            $grouped[$key][$account->id] = $name;
        }

        return $grouped;
    }

    private function getValidWithdrawalDests(): array
    {
        // make repositories
        $liabilityTypes = [AccountType::MORTGAGE, AccountType::DEBT, AccountType::CREDITCARD, AccountType::LOAN];
        $accountList    = $this->accountRepository->getActiveAccountsByType(
            [AccountType::EXPENSE, AccountType::CASH, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE]
        );
        $grouped        = [];

        // group accounts:
        /** @var Account $account */
        foreach ($accountList as $account) {
            $role = (string) $this->accountRepository->getMetaValue($account, 'account_role');
            $name = $account->name;
            if ('' === $role) {
                $role = 'no_account_type';
            }

            // maybe it's a liability thing:
            if (in_array($account->accountType->type, $liabilityTypes, true)) {
                $role = 'l_' . $account->accountType->type;
            }
            if (AccountType::CASH === $account->accountType->type) {
                $role = 'cash_account';
                $name = sprintf('(%s)', trans('firefly.cash'));
            }
            if (AccountType::EXPENSE === $account->accountType->type) {
                $role = 'expense_account';
            }

            $key                         = (string) trans('firefly.opt_group_' . $role);
            $grouped[$key][$account->id] = $name;
        }

        return $grouped;
    }

    /**
     * @throws \Exception
     */
    private function getLiabilities(): array
    {
        // make repositories
        $accountList     = $this->accountRepository->getActiveAccountsByType([AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE]);
        $defaultCurrency = app('amount')->getDefaultCurrency();
        $grouped         = [];

        // group accounts:
        /** @var Account $account */
        foreach ($accountList as $account) {
            $balance                     = app('steam')->balance($account, today());
            $currency                    = $this->accountRepository->getAccountCurrency($account) ?? $defaultCurrency;
            $role                        = 'l_' . $account->accountType->type;
            $key                         = (string) trans('firefly.opt_group_' . $role);
            $grouped[$key][$account->id] = $account->name . ' (' . app('amount')->formatAnything($currency, $balance, false) . ')';
        }

        return $grouped;
    }

    /**
     * @throws \Exception
     */
    private function getAssetAccounts(): array
    {
        // make repositories
        $accountList     = $this->accountRepository->getActiveAccountsByType([AccountType::ASSET]);
        $defaultCurrency = app('amount')->getDefaultCurrency();
        $grouped         = [];

        // group accounts:
        /** @var Account $account */
        foreach ($accountList as $account) {
            $balance  = app('steam')->balance($account, today());
            $currency = $this->accountRepository->getAccountCurrency($account) ?? $defaultCurrency;
            $role     = (string) $this->accountRepository->getMetaValue($account, 'account_role');
            if ('' === $role) {
                $role = 'no_account_type';
            }

            $key                         = (string) trans('firefly.opt_group_' . $role);
            $grouped[$key][$account->id] = $account->name . ' (' . app('amount')->formatAnything($currency, $balance, false) . ')';
        }

        return $grouped;
    }

    /**
     * Do the conversion.
     *
     * @return Redirector|RedirectResponse
     */
    public function postIndex(Request $request, TransactionType $destinationType, TransactionGroup $group)
    {
        if (!$this->isEditableGroup($group)) {
            return $this->redirectGroupToAccount($group);
        }

        /** @var TransactionJournal $journal */
        foreach ($group->transactionJournals as $journal) {
            // catch FF exception.
            try {
                $this->convertJournal($journal, $destinationType, $request->all());
            } catch (FireflyException $e) {
                session()->flash('error', $e->getMessage());

                return redirect()->route('transactions.convert.index', [strtolower($destinationType->type), $group->id])->withInput();
            }
        }

        // correct transfers:
        $group->refresh();

        session()->flash('success', (string) trans('firefly.converted_to_' . $destinationType->type));
        event(new UpdatedTransactionGroup($group, true, true));

        return redirect(route('transactions.show', [$group->id]));
    }

    /**
     * @throws FireflyException
     */
    private function convertJournal(TransactionJournal $journal, TransactionType $transactionType, array $data): TransactionJournal
    {
        /** @var AccountValidator $validator */
        $validator = app(AccountValidator::class);
        $validator->setUser(auth()->user());
        $validator->setTransactionType($transactionType->type);

        $sourceId        = $data['source_id'][$journal->id] ?? null;
        $sourceName      = $data['source_name'][$journal->id] ?? null;
        $destinationId   = $data['destination_id'][$journal->id] ?? null;
        $destinationName = $data['destination_name'][$journal->id] ?? null;

        // double check it's not an empty string.
        $sourceId         = '' === $sourceId || null === $sourceId ? null : (int) $sourceId;
        $sourceName       = '' === $sourceName ? null : (string) $sourceName;
        $destinationId    = '' === $destinationId || null === $destinationId ? null : (int) $destinationId;
        $destinationName  = '' === $destinationName ? null : (string) $destinationName;
        $validSource      = $validator->validateSource(['id' => $sourceId, 'name' => $sourceName]);
        $validDestination = $validator->validateDestination(['id' => $destinationId, 'name' => $destinationName]);

        if (false === $validSource) {
            throw new FireflyException(sprintf(trans('firefly.convert_invalid_source'), $journal->id));
        }
        if (false === $validDestination) {
            throw new FireflyException(sprintf(trans('firefly.convert_invalid_destination'), $journal->id));
        }

        // TODO typeOverrule: the account validator may have another opinion on the transaction type.

        $update = [
            'source_id'        => $sourceId,
            'source_name'      => $sourceName,
            'destination_id'   => $destinationId,
            'destination_name' => $destinationName,
            'type'             => $transactionType->type,
        ];

        // also set the currency to the currency of the source account, in case you're converting a deposit into a transfer.
        if (TransactionType::TRANSFER === $transactionType->type && TransactionType::DEPOSIT === $journal->transactionType->type) {
            $source         = $this->accountRepository->find((int) $sourceId);
            $sourceCurrency = $this->accountRepository->getAccountCurrency($source);
            $dest           = $this->accountRepository->find((int) $destinationId);
            $destCurrency   = $this->accountRepository->getAccountCurrency($dest);
            if (null !== $sourceCurrency && null !== $destCurrency && $sourceCurrency->code !== $destCurrency->code) {
                $update['currency_id']         = $sourceCurrency->id;
                $update['foreign_currency_id'] = $destCurrency->id;
                $update['foreign_amount']      = '1'; // not the best solution but at this point the amount is hard to get.
            }
        }


        /** @var JournalUpdateService $service */
        $service = app(JournalUpdateService::class);
        $service->setTransactionJournal($journal);
        $service->setData($update);
        $service->update();
        $journal->refresh();

        return $journal;
    }
}
