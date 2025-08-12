<?php

declare(strict_types=1);

namespace FireflyIII\Support\JsonApi\Enrichments;

use Carbon\Carbon;
use FireflyIII\Enums\RecurrenceRepetitionWeekend;
use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\CategoryFactory;
use FireflyIII\Models\Account;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\Note;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\Preference;
use FireflyIII\Models\Recurrence;
use FireflyIII\Models\RecurrenceRepetition;
use FireflyIII\Models\RecurrenceTransaction;
use FireflyIII\Models\RecurrenceTransactionMeta;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionType;
use FireflyIII\Models\UserGroup;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Facades\Preferences;
use FireflyIII\Support\Http\Api\ExchangeRateConverter;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class RecurringEnrichment implements EnrichmentInterface
{
    private Collection          $collection;
    private array               $ids                   = [];
    private array               $transactionTypeIds    = [];
    private array               $transactionTypes      = [];
    private array               $notes                 = [];
    private array               $repetitions           = [];
    private array               $transactions          = [];
    private User                $user;
    private UserGroup           $userGroup;
    private string              $language              = 'en_US';
    private array               $currencyIds           = [];
    private array               $foreignCurrencyIds    = [];
    private array               $sourceAccountIds      = [];
    private array               $destinationAccountIds = [];
    private array               $accounts              = [];
    private array               $currencies            = [];
    private array               $recurrenceIds         = [];
    private TransactionCurrency $primaryCurrency;
    private bool                $convertToPrimary      = false;

    public function __construct()
    {
        $this->primaryCurrency  = Amount::getPrimaryCurrency();
        $this->convertToPrimary = Amount::convertToPrimary();
    }

    public function enrich(Collection $collection): Collection
    {
        $this->collection = $collection;
        $this->collectIds();
        $this->collectRepetitions();
        $this->collectTransactions();
        $this->collectCurrencies();
        $this->collectNotes();
        $this->collectAccounts();
        $this->collectTransactionMetaData();

        $this->appendCollectedData();

        return $this->collection;
    }

    public function enrichSingle(array|Model $model): array|Model
    {
        Log::debug(__METHOD__);
        $collection = new Collection([$model]);
        $collection = $this->enrich($collection);

        return $collection->first();
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
        $this->setUserGroup($user->userGroup);
        $this->getLanguage();
    }

    public function setUserGroup(UserGroup $userGroup): void
    {
        $this->userGroup = $userGroup;
    }

    private function collectIds(): void
    {
        /** @var Recurrence $recurrence */
        foreach ($this->collection as $recurrence) {
            $id                            = (int)$recurrence->id;
            $typeId                        = (int)$recurrence->transaction_type_id;
            $this->ids[]                   = $id;
            $this->transactionTypeIds[$id] = $typeId;
        }
        $this->ids        = array_unique($this->ids);

        // collect transaction types.
        $transactionTypes = TransactionType::whereIn('id', array_unique($this->transactionTypeIds))->get();
        foreach ($transactionTypes as $transactionType) {
            $id                          = (int)$transactionType->id;
            $this->transactionTypes[$id] = TransactionTypeEnum::from($transactionType->type);
        }
    }

    private function collectRepetitions(): void
    {
        Log::debug('Start of enrichment: collectRepetitions()');
        $repository = app(RecurringRepositoryInterface::class);
        $repository->setUserGroup($this->userGroup);
        $set        = RecurrenceRepetition::whereIn('recurrence_id', $this->ids)->get();

        /** @var RecurrenceRepetition $repetition */
        foreach ($set as $repetition) {
            $recurrence                     = $this->collection->filter(function (Recurrence $item) use ($repetition) {
                return (int)$item->id === (int)$repetition->recurrence_id;
            })->first();
            $fromDate                       = $recurrence->latest_date ?? $recurrence->first_date;
            $id                             = (int)$repetition->recurrence_id;
            $repId                          = (int)$repetition->id;
            $this->repetitions[$id] ??= [];

            // get the (future) occurrences for this specific type of repetition:
            $amount                         = 'daily' === $repetition->repetition_type ? 9 : 5;
            $set                            = $repository->getXOccurrencesSince($repetition, $fromDate, now(config('app.timezone')), $amount);

            /** @var Carbon $carbon */
            foreach ($set as $carbon) {
                $occurrences[] = $carbon->toAtomString();
            }

            $this->repetitions[$id][$repId] = [
                'id'          => (string)$repId,
                'created_at'  => $repetition->created_at->toAtomString(),
                'updated_at'  => $repetition->updated_at->toAtomString(),
                'type'        => $repetition->repetition_type,
                'moment'      => (string)$repetition->moment,
                'skip'        => (int)$repetition->skip,
                'weekend'     => RecurrenceRepetitionWeekend::from((int)$repetition->weekend),
                'description' => $this->getRepetitionDescription($repetition),
                'occurrences' => $occurrences,
            ];
        }
        Log::debug('End of enrichment: collectRepetitions()');
    }

    private function collectTransactions(): void
    {
        $set = RecurrenceTransaction::whereIn('recurrence_id', $this->ids)->get();

        /** @var RecurrenceTransaction $transaction */
        foreach ($set as $transaction) {
            $id                                          = (int)$transaction->recurrence_id;
            $transactionId                               = (int)$transaction->id;
            $this->recurrenceIds[$transactionId]         = $id;
            $this->transactions[$id] ??= [];
            $amount                                      = $transaction->amount;
            $foreignAmount                               = $transaction->foreign_amount;

            $this->transactions[$id][$transactionId]     = [
                'id'                          => (string)$transactionId,
                // 'recurrence_id'               => $id,
                'transaction_currency_id'     => (int)$transaction->transaction_currency_id,
                'foreign_currency_id'         => null === $transaction->foreign_currency_id ? null : (int)$transaction->foreign_currency_id,
                'source_id'                   => (int)$transaction->source_id,
                'object_has_currency_setting' => true,
                'destination_id'              => (int)$transaction->destination_id,
                'amount'                      => $amount,
                'foreign_amount'              => $foreignAmount,
                'pc_amount'                   => null,
                'pc_foreign_amount'           => null,
                'description'                 => $transaction->description,
                'tags'                        => [],
                'category_id'                 => null,
                'category_name'               => null,
                'budget_id'                   => null,
                'budget_name'                 => null,
                'piggy_bank_id'               => null,
                'piggy_bank_name'             => null,
                'subscription_id'             => null,
                'subscription_name'           => null,

            ];
            // collect all kinds of meta data to be collected later.
            $this->currencyIds[$transactionId]           = (int)$transaction->transaction_currency_id;
            $this->sourceAccountIds[$transactionId]      = (int)$transaction->source_id;
            $this->destinationAccountIds[$transactionId] = (int)$transaction->destination_id;
            if (null !== $transaction->foreign_currency_id) {
                $this->foreignCurrencyIds[$transactionId] = (int)$transaction->foreign_currency_id;
            }
        }
    }

    private function appendCollectedData(): void
    {
        $this->collection = $this->collection->map(function (Recurrence $item) {
            $id         = (int)$item->id;
            $meta       = [
                'notes'        => $this->notes[$id] ?? null,
                'repetitions'  => array_values($this->repetitions[$id] ?? []),
                'transactions' => $this->processTransactions(array_values($this->transactions[$id] ?? [])),
            ];

            $item->meta = $meta;

            return $item;
        });
    }

    /**
     * Parse the repetition in a string that is user readable.
     * TODO duplicate with repository.
     */
    public function getRepetitionDescription(RecurrenceRepetition $repetition): string
    {
        if ('daily' === $repetition->repetition_type) {
            return (string)trans('firefly.recurring_daily', [], $this->language);
        }
        if ('weekly' === $repetition->repetition_type) {
            $dayOfWeek = trans(sprintf('config.dow_%s', $repetition->repetition_moment), [], $this->language);
            if ($repetition->repetition_skip > 0) {
                return (string)trans('firefly.recurring_weekly_skip', ['weekday' => $dayOfWeek, 'skip' => $repetition->repetition_skip + 1], $this->language);
            }

            return (string)trans('firefly.recurring_weekly', ['weekday' => $dayOfWeek], $this->language);
        }
        if ('monthly' === $repetition->repetition_type) {
            if ($repetition->repetition_skip > 0) {
                return (string)trans('firefly.recurring_monthly_skip', ['dayOfMonth' => $repetition->repetition_moment, 'skip' => $repetition->repetition_skip + 1], $this->language);
            }

            return (string)trans('firefly.recurring_monthly', ['dayOfMonth' => $repetition->repetition_moment, 'skip' => $repetition->repetition_skip - 1], $this->language);
        }
        if ('ndom' === $repetition->repetition_type) {
            $parts     = explode(',', $repetition->repetition_moment);
            // first part is number of week, second is weekday.
            $dayOfWeek = trans(sprintf('config.dow_%s', $parts[1]), [], $this->language);
            if ($repetition->repetition_skip > 0) {
                return (string)trans('firefly.recurring_ndom_skip', ['skip' => $repetition->repetition_skip, 'weekday' => $dayOfWeek, 'dayOfMonth' => $parts[0]], $this->language);
            }

            return (string)trans('firefly.recurring_ndom', ['weekday' => $dayOfWeek, 'dayOfMonth' => $parts[0]], $this->language);
        }
        if ('yearly' === $repetition->repetition_type) {
            $today   = today(config('app.timezone'))->endOfYear();
            $repDate = Carbon::createFromFormat('Y-m-d', $repetition->repetition_moment);
            if (!$repDate instanceof Carbon) {
                $repDate = clone $today;
            }
            // $diffInYears = (int)$today->diffInYears($repDate, true);
            // $repDate->addYears($diffInYears); // technically not necessary.
            $string  = $repDate->isoFormat((string)trans('config.month_and_day_no_year_js'));

            return (string)trans('firefly.recurring_yearly', ['date' => $string], $this->language);
        }

        return '';
    }

    private function getLanguage(): void
    {
        /** @var Preference $preference */
        $preference     = Preferences::getForUser($this->user, 'language', config('firefly.default_language', 'en_US'));
        $language       = $preference->data;
        if (is_array($language)) {
            $language = 'en_US';
        }
        $language       = (string)$language;
        $this->language = $language;
    }

    private function collectCurrencies(): void
    {
        $all        = array_merge(array_unique($this->currencyIds), array_unique($this->foreignCurrencyIds));
        $currencies = TransactionCurrency::whereIn('id', array_unique($all))->get();
        foreach ($currencies as $currency) {
            $id                    = (int)$currency->id;
            $this->currencies[$id] = $currency;
        }
    }

    private function processTransactions(array $transactions): array
    {
        $return    = [];
        $converter = new ExchangeRateConverter();
        foreach ($transactions as $transaction) {
            $currencyId                                     = $transaction['transaction_currency_id'];
            $pcAmount                                       = null;
            $pcForeignAmount                                = null;
            // set the same amount in the primary currency, if both are the same anyway.
            if (true === $this->convertToPrimary && $currencyId === (int)$this->primaryCurrency->id) {
                $pcAmount = $transaction['amount'];
            }
            // convert the amount to the primary currency, if it is not the same.
            if (true === $this->convertToPrimary && $currencyId !== (int)$this->primaryCurrency->id) {
                $pcAmount = $converter->convert($this->currencies[$currencyId], $this->primaryCurrency, today(), $transaction['amount']);
            }
            if (null !== $transaction['foreign_amount'] && null !== $transaction['foreign_currency_id']) {
                $foreignCurrencyId = $transaction['foreign_currency_id'];
                if ($foreignCurrencyId !== $this->primaryCurrency->id) {
                    $pcForeignAmount = $converter->convert($this->currencies[$foreignCurrencyId], $this->primaryCurrency, today(), $transaction['foreign_amount']);
                }
            }

            $transaction['pc_amount']                       = $pcAmount;
            $transaction['pc_foreign_amount']               = $pcForeignAmount;

            $sourceId                                       = $transaction['source_id'];
            $transaction['source_name']                     = $this->accounts[$sourceId]->name;
            $transaction['source_iban']                     = $this->accounts[$sourceId]->iban;
            $transaction['source_type']                     = $this->accounts[$sourceId]->accountType->type;
            $transaction['source_id']                       = (string)$transaction['source_id'];

            $destId                                         = $transaction['destination_id'];
            $transaction['destination_name']                = $this->accounts[$destId]->name;
            $transaction['destination_iban']                = $this->accounts[$destId]->iban;
            $transaction['destination_type']                = $this->accounts[$destId]->accountType->type;
            $transaction['destination_id']                  = (string)$transaction['destination_id'];

            $transaction['currency_id']                     = (string)$currencyId;
            $transaction['currency_name']                   = $this->currencies[$currencyId]->name;
            $transaction['currency_code']                   = $this->currencies[$currencyId]->code;
            $transaction['currency_symbol']                 = $this->currencies[$currencyId]->symbol;
            $transaction['currency_decimal_places']         = $this->currencies[$currencyId]->decimal_places;

            $transaction['primary_currency_id']             = (string)$this->primaryCurrency->id;
            $transaction['primary_currency_name']           = $this->primaryCurrency->name;
            $transaction['primary_currency_code']           = $this->primaryCurrency->code;
            $transaction['primary_currency_symbol']         = $this->primaryCurrency->symbol;
            $transaction['primary_currency_decimal_places'] = $this->primaryCurrency->decimal_places;

            // $transaction['foreign_currency_id'] = null;
            $transaction['foreign_currency_name']           = null;
            $transaction['foreign_currency_code']           = null;
            $transaction['foreign_currency_symbol']         = null;
            $transaction['foreign_currency_decimal_places'] = null;
            if (null !== $transaction['foreign_currency_id']) {
                $currencyId                                     = $transaction['foreign_currency_id'];
                $transaction['foreign_currency_id']             = (string)$currencyId;
                $transaction['foreign_currency_name']           = $this->currencies[$currencyId]->name;
                $transaction['foreign_currency_code']           = $this->currencies[$currencyId]->code;
                $transaction['foreign_currency_symbol']         = $this->currencies[$currencyId]->symbol;
                $transaction['foreign_currency_decimal_places'] = $this->currencies[$currencyId]->decimal_places;
            }
            unset($transaction['transaction_currency_id']);
            $return[]                                       = $transaction;
        }

        return $return;
    }

    private function collectAccounts(): void
    {
        $all      = array_merge(array_unique($this->sourceAccountIds), array_unique($this->destinationAccountIds));
        $accounts = Account::with(['accountType'])->whereIn('id', array_unique($all))->get();

        /** @var Account $account */
        foreach ($accounts as $account) {
            $id                  = (int)$account->id;
            $this->accounts[$id] = $account;
        }
    }

    private function collectTransactionMetaData(): void
    {
        $ids           = array_keys($this->transactions);
        $meta          = RecurrenceTransactionMeta::whereIn('rt_id', $ids)->get();
        // other meta-data to be collected:
        $billIds       = [];
        $piggyBankIds  = [];
        $categoryIds   = [];
        $categoryNames = [];
        $budgetIds     = [];
        foreach ($meta as $entry) {
            $id            = (int)$entry->id;
            $transactionId = (int)$entry->rt_id;
            $recurrenceId  = $this->recurrenceIds[$transactionId];
            $name          = (string)$entry->name;

            switch ($name) {
                default:
                    throw new FireflyException(sprintf('Recurrence transformer cant handle field "%s"', $name));

                case 'bill_id':
                    if ((int)$entry->value > 0) {
                        $this->transactions[$recurrenceId][$transactionId]['subscription_id'] = $entry->value;
                        if (!array_key_exists($id, $billIds)) {
                            $billIds[$id] = [
                                'recurrence_id'  => $recurrenceId,
                                'transaction_id' => $transactionId,
                                'bill_id'        => (int)$entry->value,
                            ];
                        }
                    }

                    break;

                case 'tags':
                    $this->transactions[$recurrenceId][$transactionId]['tags'] = json_decode((string)$entry->value);

                    break;

                case 'piggy_bank_id':
                    if ((int)$entry->value > 0) {
                        $this->transactions[$recurrenceId][$transactionId]['piggy_bank_id'] = (string)$entry->value;
                        if (!array_key_exists($id, $piggyBankIds)) {
                            $piggyBankIds[$id] = [
                                'recurrence_id'  => $recurrenceId,
                                'transaction_id' => $transactionId,
                                'piggy_bank_id'  => (int)$entry->value,
                            ];
                        }
                    }

                    break;

                case 'category_id':
                    if ((int)$entry->value > 0) {
                        $this->transactions[$recurrenceId][$transactionId]['category_id'] = (string)$entry->value;
                        if (!array_key_exists($id, $categoryIds)) {
                            $categoryIds[$id] = [
                                'recurrence_id'  => $recurrenceId,
                                'transaction_id' => $transactionId,
                                'category_id'    => (int)$entry->value,
                            ];
                        }
                    }

                    break;

                case 'category_name':
                    if ('' !== (string)$entry->value) {
                        $this->transactions[$recurrenceId][$transactionId]['category_name'] = (string)$entry->value;
                        if (!array_key_exists($id, $categoryIds)) {
                            $categoryNames[$id] = [
                                'recurrence_id'  => $recurrenceId,
                                'transaction_id' => $transactionId,
                                'category_name'  => $entry->value,
                            ];
                        }
                    }

                    break;

                case 'budget_id':
                    if ((int)$entry->value > 0) {
                        $this->transactions[$recurrenceId][$transactionId]['budget_id'] = (string)$entry->value;
                        if (!array_key_exists($id, $budgetIds)) {
                            $budgetIds[$id] = [
                                'recurrence_id'  => $recurrenceId,
                                'transaction_id' => $transactionId,
                                'budget_id'      => (int)$entry->value,
                            ];
                        }
                    }

                    break;
            }
        }
        $this->collectBillInfo($billIds);
        $this->collectPiggyBankInfo($piggyBankIds);
        $this->collectCategoryIdInfo($categoryIds);
        $this->collectCategoryNameInfo($categoryNames);
        $this->collectBudgetInfo($budgetIds);
    }

    private function collectBillInfo(array $billIds): void
    {
        if (0 === count($billIds)) {
            return;
        }
        $ids    = Arr::pluck($billIds, 'bill_id');
        $bills  = Bill::whereIn('id', $ids)->get();
        $mapped = [];
        foreach ($bills as $bill) {
            $mapped[(int)$bill->id] = $bill;
        }
        foreach ($billIds as $info) {
            $recurrenceId                                                           = $info['recurrence_id'];
            $transactionId                                                          = $info['transaction_id'];
            $this->transactions[$recurrenceId][$transactionId]['subscription_name'] = $mapped[$info['bill_id']]->name ?? '';
        }
    }

    private function collectPiggyBankInfo(array $piggyBankIds): void
    {
        if (0 === count($piggyBankIds)) {
            return;
        }
        $ids        = Arr::pluck($piggyBankIds, 'piggy_bank_id');
        $piggyBanks = PiggyBank::whereIn('id', $ids)->get();
        $mapped     = [];
        foreach ($piggyBanks as $piggyBank) {
            $mapped[(int)$piggyBank->id] = $piggyBank;
        }
        foreach ($piggyBankIds as $info) {
            $recurrenceId                                                         = $info['recurrence_id'];
            $transactionId                                                        = $info['transaction_id'];
            $this->transactions[$recurrenceId][$transactionId]['piggy_bank_name'] = $mapped[$info['piggy_bank_id']]->name ?? '';
        }
    }

    private function collectCategoryIdInfo(array $categoryIds): void
    {
        if (0 === count($categoryIds)) {
            return;
        }
        $ids        = Arr::pluck($categoryIds, 'category_id');
        $categories = Category::whereIn('id', $ids)->get();
        $mapped     = [];
        foreach ($categories as $category) {
            $mapped[(int)$category->id] = $category;
        }
        foreach ($categoryIds as $info) {
            $recurrenceId                                                       = $info['recurrence_id'];
            $transactionId                                                      = $info['transaction_id'];
            $this->transactions[$recurrenceId][$transactionId]['category_name'] = $mapped[$info['category_id']]->name ?? '';
        }
    }

    /**
     * TODO This method does look-up in a loop.
     */
    private function collectCategoryNameInfo(array $categoryNames): void
    {
        if (0 === count($categoryNames)) {
            return;
        }
        $factory = app(CategoryFactory::class);
        $factory->setUser($this->user);
        foreach ($categoryNames as $info) {
            $recurrenceId  = $info['recurrence_id'];
            $transactionId = $info['transaction_id'];
            $category      = $factory->findOrCreate(null, $info['category_name']);
            if (null !== $category) {
                $this->transactions[$recurrenceId][$transactionId]['category_id']   = (string)$category->id;
                $this->transactions[$recurrenceId][$transactionId]['category_name'] = $category->name;
            }
        }
    }

    private function collectBudgetInfo(array $budgetIds): void
    {
        if (0 === count($budgetIds)) {
            return;
        }
        $ids        = Arr::pluck($budgetIds, 'budget_id');
        $categories = Budget::whereIn('id', $ids)->get();
        $mapped     = [];
        foreach ($categories as $category) {
            $mapped[(int)$category->id] = $category;
        }
        foreach ($budgetIds as $info) {
            $recurrenceId                                                     = $info['recurrence_id'];
            $transactionId                                                    = $info['transaction_id'];
            $this->transactions[$recurrenceId][$transactionId]['budget_name'] = $mapped[$info['budget_id']]->name ?? '';
        }
    }

    private function collectNotes(): void
    {
        $notes = Note::query()->whereIn('noteable_id', $this->ids)
            ->whereNotNull('notes.text')
            ->where('notes.text', '!=', '')
            ->where('noteable_type', Recurrence::class)->get(['notes.noteable_id', 'notes.text'])->toArray()
        ;
        foreach ($notes as $note) {
            $this->notes[(int)$note['noteable_id']] = (string)$note['text'];
        }
        Log::debug(sprintf('Enrich with %d note(s)', count($this->notes)));
    }
}
