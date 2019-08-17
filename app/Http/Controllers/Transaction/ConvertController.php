<?php
/**
 * ConvertController.php
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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Transaction;

use Carbon\Carbon;
use FireflyIII\Events\UpdatedTransactionGroup;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Services\Internal\Update\JournalUpdateService;
use FireflyIII\Support\Http\Controllers\ModelInformation;
use FireflyIII\Support\Http\Controllers\UserNavigation;
use FireflyIII\Transformers\TransactionGroupTransformer;
use FireflyIII\Validation\AccountValidator;
use Illuminate\Http\Request;
use Log;
use View;


/**
 * Class ConvertController.
 *
 * TODO when converting to a split transfer, all sources and destinations must be the same.
 */
class ConvertController extends Controller
{
    use ModelInformation, UserNavigation;

    /** @var JournalRepositoryInterface Journals and transactions overview */
    private $repository;

    /**
     * ConvertController constructor.
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();

        // some useful repositories:
        $this->middleware(
            function ($request, $next) {
                $this->repository = app(JournalRepositoryInterface::class);

                app('view')->share('title', (string)trans('firefly.transactions'));
                app('view')->share('mainTitleIcon', 'fa-exchange');

                return $next($request);
            }
        );
    }


    /**
     * Show overview of a to be converted transaction.
     *
     * @param TransactionType $destinationType
     * @param TransactionGroup $group
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|View
     * @throws \Exception
     */
    public function index(TransactionType $destinationType, TransactionGroup $group)
    {
        if (!$this->isEditableGroup($group)) {
            return $this->redirectGroupToAccount($group); // @codeCoverageIgnore
        }

        /** @var TransactionGroupTransformer $transformer */
        $transformer = app(TransactionGroupTransformer::class);

        /** @var TransactionJournal $first */
        $first      = $group->transactionJournals()->first();
        $sourceType = $first->transactionType;

        $groupTitle   = $group->title ?? $first->description;
        $groupArray   = $transformer->transformObject($group);
        $subTitle     = (string)trans('firefly.convert_to_' . $destinationType->type, ['description' => $groupTitle]);
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
            Log::debug('This is already a transaction of the expected type..');
            session()->flash('info', (string)trans('firefly.convert_is_already_type_' . $destinationType->type));

            return redirect(route('transactions.show', [$group->id]));
        }

        return view(
            'transactions.convert', compact(
                                      'sourceType', 'destinationType',
                                      'group', 'groupTitle', 'groupArray', 'assets', 'validDepositSources', 'liabilities',
                                      'validWithdrawalDests', 'preFilled',
                                      'subTitle', 'subTitleIcon'
                                  )
        );
    }

    /**
     * Do the conversion.
     *
     * @param Request $request
     * @param TransactionType $destinationType
     * @param TransactionGroup $group
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     *
     * @throws FireflyException
     */
    public function postIndex(Request $request, TransactionType $destinationType, TransactionGroup $group)
    {
        if (!$this->isEditableGroup($group)) {
            return $this->redirectGroupToAccount($group); // @codeCoverageIgnore
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
        $this->correctTransfer($group);

        session()->flash('success', (string)trans('firefly.converted_to_' . $destinationType->type));
        event(new UpdatedTransactionGroup($group));

        return redirect(route('transactions.show', [$group->id]));
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function getAssetAccounts(): array
    {
        // make repositories
        /** @var AccountRepositoryInterface $repository */
        $repository      = app(AccountRepositoryInterface::class);
        $accountList     = $repository->getActiveAccountsByType([AccountType::ASSET]);
        $defaultCurrency = app('amount')->getDefaultCurrency();
        $grouped         = [];
        // group accounts:
        /** @var Account $account */
        foreach ($accountList as $account) {
            $balance  = app('steam')->balance($account, new Carbon);
            $currency = $repository->getAccountCurrency($account) ?? $defaultCurrency;
            $role     = (string)$repository->getMetaValue($account, 'account_role');
            if ('' === $role) {
                $role = 'no_account_type'; // @codeCoverageIgnore
            }

            $key                         = (string)trans('firefly.opt_group_' . $role);
            $grouped[$key][$account->id] = $account->name . ' (' . app('amount')->formatAnything($currency, $balance, false) . ')';
        }

        return $grouped;
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function getLiabilities(): array
    {
        // make repositories
        /** @var AccountRepositoryInterface $repository */
        $repository      = app(AccountRepositoryInterface::class);
        $accountList     = $repository->getActiveAccountsByType([AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE]);
        $defaultCurrency = app('amount')->getDefaultCurrency();
        $grouped         = [];
        // group accounts:
        /** @var Account $account */
        foreach ($accountList as $account) {
            $balance                     = app('steam')->balance($account, new Carbon);
            $currency                    = $repository->getAccountCurrency($account) ?? $defaultCurrency;
            $role                        = 'l_' . $account->accountType->type;
            $key                         = (string)trans('firefly.opt_group_' . $role);
            $grouped[$key][$account->id] = $account->name . ' (' . app('amount')->formatAnything($currency, $balance, false) . ')';
        }

        return $grouped;
    }

    /**
     * @return array
     */
    private function getValidDepositSources(): array
    {
        // make repositories
        /** @var AccountRepositoryInterface $repository */
        $repository     = app(AccountRepositoryInterface::class);
        $liabilityTypes = [AccountType::MORTGAGE, AccountType::DEBT, AccountType::CREDITCARD, AccountType::LOAN];
        $accountList    = $repository
            ->getActiveAccountsByType([AccountType::REVENUE, AccountType::CASH, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE]);
        $grouped        = [];
        // group accounts:
        /** @var Account $account */
        foreach ($accountList as $account) {
            $role = (string)$repository->getMetaValue($account, 'account_role');
            $name = $account->name;
            if ('' === $role) {
                $role = 'no_account_type'; // @codeCoverageIgnore
            }

            // maybe it's a liability thing:
            if (in_array($account->accountType->type, $liabilityTypes, true)) {
                $role = 'l_' . $account->accountType->type; // @codeCoverageIgnore
            }
            if (AccountType::CASH === $account->accountType->type) {
                // @codeCoverageIgnoreStart
                $role = 'cash_account';
                $name = sprintf('(%s)', trans('firefly.cash'));
                // @codeCoverageIgnoreEnd
            }
            if (AccountType::REVENUE === $account->accountType->type) {
                $role = 'revenue_account'; // @codeCoverageIgnore
            }

            $key                         = (string)trans('firefly.opt_group_' . $role);
            $grouped[$key][$account->id] = $name;
        }

        return $grouped;
    }

    /**
     * @return array
     */
    private function getValidWithdrawalDests(): array
    {
        // make repositories
        /** @var AccountRepositoryInterface $repository */
        $repository     = app(AccountRepositoryInterface::class);
        $liabilityTypes = [AccountType::MORTGAGE, AccountType::DEBT, AccountType::CREDITCARD, AccountType::LOAN];
        $accountList    = $repository
            ->getActiveAccountsByType([AccountType::EXPENSE, AccountType::CASH, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE]);
        $grouped        = [];
        // group accounts:
        /** @var Account $account */
        foreach ($accountList as $account) {
            $role = (string)$repository->getMetaValue($account, 'account_role');
            $name = $account->name;
            if ('' === $role) {
                $role = 'no_account_type'; // @codeCoverageIgnore
            }

            // maybe it's a liability thing:
            if (in_array($account->accountType->type, $liabilityTypes, true)) {
                $role = 'l_' . $account->accountType->type; // @codeCoverageIgnore
            }
            if (AccountType::CASH === $account->accountType->type) {
                // @codeCoverageIgnoreStart
                $role = 'cash_account';
                $name = sprintf('(%s)', trans('firefly.cash'));
                // @codeCoverageIgnoreEnd
            }
            if (AccountType::EXPENSE === $account->accountType->type) {
                $role = 'expense_account'; // @codeCoverageIgnore
            }

            $key                         = (string)trans('firefly.opt_group_' . $role);
            $grouped[$key][$account->id] = $name;
        }

        return $grouped;
    }

    /**
     * @param TransactionJournal $journal
     * @param TransactionType $transactionType
     * @param array $data
     * @return TransactionJournal
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

        // double check its not an empty string.
        $sourceId         = '' === $sourceId || null === $sourceId ? null : (int)$sourceId;
        $sourceName       = '' === $sourceName ? null : $sourceName;
        $destinationId    = '' === $destinationId || null === $destinationId ? null : (int)$destinationId;
        $destinationName  = '' === $destinationName ? null : $destinationName;
        $validSource      = $validator->validateSource($sourceId, $sourceName);
        $validDestination = $validator->validateDestination($destinationId, $destinationName);

        if (false === $validSource) {
            throw new FireflyException(sprintf(trans('firefly.convert_invalid_source'), $journal->id));
        }
        if (false === $validDestination) {
            throw new FireflyException(sprintf(trans('firefly.convert_invalid_destination'), $journal->id));
        }

        $update = [
            'source_id'        => $sourceId,
            'source_name'      => $sourceName,
            'destination_id'   => $destinationId,
            'destination_name' => $destinationName,
            'type'             => $transactionType->type,
        ];
        /** @var JournalUpdateService $service */
        $service = app(JournalUpdateService::class);
        $service->setTransactionJournal($journal);
        $service->setData($update);
        $service->update();
        $journal->refresh();

        return $journal;
    }

    /**
     * @param TransactionGroup $group
     */
    private function correctTransfer(TransactionGroup $group): void
    {
    }
}
