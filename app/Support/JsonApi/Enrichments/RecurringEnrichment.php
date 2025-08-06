<?php

namespace FireflyIII\Support\JsonApi\Enrichments;

use Carbon\Carbon;
use FireflyIII\Enums\RecurrenceRepetitionWeekend;
use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Models\Preference;
use FireflyIII\Models\Recurrence;
use FireflyIII\Models\RecurrenceRepetition;
use FireflyIII\Models\TransactionType;
use FireflyIII\Models\UserGroup;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use FireflyIII\Support\Facades\Preferences;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class RecurringEnrichment implements EnrichmentInterface
{
    private Collection $collection;
    private array      $ids                = [];
    private array      $transactionTypeIds = [];
    private array      $transactionTypes   = [];
    private array      $repetitions        = [];
    private User       $user;
    private UserGroup  $userGroup;
    private string     $language           = 'en_US';

    public function enrich(Collection $collection): Collection
    {
        $this->collection = $collection;
        $this->collectIds();
        $this->collectRepetitions();
        $this->collectTransactions();

        $this->appendCollectedData();

        return $this->collection;
    }

    public function enrichSingle(Model|array $model): array|Model
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
        $this->ids = array_unique($this->ids);

        // collect transaction types.
        $transactionTypes = TransactionType::whereIn('id', array_unique($this->transactionTypeIds))->get();
        foreach ($transactionTypes as $transactionType) {
            $id                          = (int)$transactionType->id;
            $this->transactionTypes[$id] = TransactionTypeEnum::from($transactionType->type);
        }
    }

    private function collectRepetitions(): void
    {
        $repository = app(RecurringRepositoryInterface::class);
        $repository->setUserGroup($this->userGroup);
        $set = RecurrenceRepetition::whereIn('recurrence_id', $this->ids)->get();
        /** @var RecurrenceRepetition $repetition */
        foreach ($set as $repetition) {
            $recurrence             = $this->collection->filter(function (Recurrence $item) use ($repetition) {
                return (int)$item->id === (int)$repetition->recurrence_id;
            })->first();
            $fromDate               = $recurrence->latest_date ?? $recurrence->first_date;
            $id                     = (int)$repetition->recurrence_id;
            $repId                  = (int)$repetition->id;
            $this->repetitions[$id] ??= [];

            // get the (future) occurrences for this specific type of repetition:
            $amount = 'daily' === $repetition->repetition_type ? 9 : 5;
            $set    = $repository->getXOccurrencesSince($repetition, $fromDate, now(config('app.timezone')), $amount);
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
    }

    private function collectTransactions(): void
    {
    }

    private function appendCollectedData(): void
    {
        $this->collection = $this->collection->map(function (Recurrence $item) {
            $id   = (int)$item->id;
            $meta = [
                'repetitions' => array_values($this->repetitions[$id] ?? []),
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
            $parts = explode(',', $repetition->repetition_moment);
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
            //$repDate->addYears($diffInYears); // technically not necessary.
            $string = $repDate->isoFormat((string)trans('config.month_and_day_no_year_js'));

            return (string)trans('firefly.recurring_yearly', ['date' => $string], $this->language);
        }

        return '';
    }

    private function getLanguage(): void
    {
        /** @var Preference $preference */
        $preference = Preferences::getForUser($this->user, 'language', config('firefly.default_language', 'en_US'));
        $language   = $preference->data;
        if (is_array($language)) {
            $language = 'en_US';
        }
        $language       = (string)$language;
        $this->language = $language;
    }
}
