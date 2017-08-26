<?php
/**
 * TagRepository.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Repositories\Tag;


use Carbon\Carbon;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;

/**
 * Class TagRepository
 *
 * @package FireflyIII\Repositories\Tag
 */
class TagRepository implements TagRepositoryInterface
{

    /** @var User */
    private $user;

    /**
     *
     * @param TransactionJournal $journal
     * @param Tag                $tag
     *
     * @return bool
     */
    public function connect(TransactionJournal $journal, Tag $tag): bool
    {
        /*
         * Already connected:
         */
        if ($journal->tags()->find($tag->id)) {
            Log::info(sprintf('Tag #%d is already connected to journal #%d.', $tag->id, $journal->id));

            return false;
        }
        Log::debug(sprintf('Tag #%d connected', $tag->id));
        $journal->tags()->save($tag);
        $journal->save();

        return true;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->user->tags()->count();
    }

    /**
     * @param Tag $tag
     *
     * @return bool
     */
    public function destroy(Tag $tag): bool
    {
        $tag->delete();

        return true;
    }

    /**
     * @param Tag    $tag
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return string
     */
    public function earnedInPeriod(Tag $tag, Carbon $start, Carbon $end): string
    {
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setUser($this->user);
        $collector->setRange($start, $end)->setTypes([TransactionType::DEPOSIT])->setAllAssetAccounts()->setTag($tag);
        $set = $collector->getJournals();
        $sum = strval($set->sum('transaction_amount'));

        return $sum;
    }

    /**
     * @param int $tagId
     *
     * @return Tag
     */
    public function find(int $tagId): Tag
    {
        $tag = $this->user->tags()->find($tagId);
        if (is_null($tag)) {
            $tag = new Tag;
        }

        return $tag;
    }

    /**
     * @param string $tag
     *
     * @return Tag
     */
    public function findByTag(string $tag): Tag
    {
        $tags = $this->user->tags()->get();
        /** @var Tag $tag */
        foreach ($tags as $databaseTag) {
            if ($databaseTag->tag === $tag) {
                return $databaseTag;
            }
        }

        return new Tag;
    }

    /**
     * @param Tag $tag
     *
     * @return Carbon
     */
    public function firstUseDate(Tag $tag): Carbon
    {
        $journal = $tag->transactionJournals()->orderBy('date', 'ASC')->first();
        if (!is_null($journal)) {
            return $journal->date;
        }

        return new Carbon;
    }

    /**
     * @return Collection
     */
    public function get(): Collection
    {
        /** @var Collection $tags */
        $tags = $this->user->tags()->get();
        $tags = $tags->sortBy(
            function (Tag $tag) {
                return strtolower($tag->tag);
            }
        );

        return $tags;
    }

    /**
     * @param string $type
     *
     * @return Collection
     */
    public function getByType(string $type): Collection
    {
        return $this->user->tags()->where('tagMode', $type)->orderBy('date', 'ASC')->get();
    }

    /**
     * @param Tag $tag
     *
     * @return Carbon
     */
    public function lastUseDate(Tag $tag): Carbon
    {
        $journal = $tag->transactionJournals()->orderBy('date', 'DESC')->first();
        if (!is_null($journal)) {
            return $journal->date;
        }

        return new Carbon;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param Tag    $tag
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return string
     */
    public function spentInPeriod(Tag $tag, Carbon $start, Carbon $end): string
    {
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setUser($this->user);
        $collector->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL])->setAllAssetAccounts()->setTag($tag);
        $set = $collector->getJournals();
        $sum = strval($set->sum('transaction_amount'));

        return $sum;
    }

    /**
     * @param array $data
     *
     * @return Tag
     */
    public function store(array $data): Tag
    {
        $tag              = new Tag;
        $tag->tag         = $data['tag'];
        $tag->date        = $data['date'];
        $tag->description = $data['description'];
        $tag->latitude    = $data['latitude'];
        $tag->longitude   = $data['longitude'];
        $tag->zoomLevel   = $data['zoomLevel'];
        $tag->tagMode     = 'nothing';
        $tag->user()->associate($this->user);
        $tag->save();

        return $tag;


    }

    /**
     * @param Tag         $tag
     * @param Carbon|null $start
     * @param Carbon|null $end
     *
     * @return string
     */
    public function sumOfTag(Tag $tag, ?Carbon $start, ?Carbon $end): string
    {
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);

        if (!is_null($start) && !is_null($end)) {
            $collector->setRange($start, $end);
        }

        $collector->setAllAssetAccounts()->setTag($tag);
        $journals = $collector->getJournals();
        $sum      = '0';
        foreach ($journals as $journal) {
            $sum = bcadd($sum, app('steam')->positive(strval($journal->transaction_amount)));
        }

        return strval($sum);
    }

    /**
     * Generates a tag cloud.
     *
     * @param int|null $year
     *
     * @return array
     */
    public function tagCloud(?int $year): array
    {
        $min    = null;
        $max    = 0;
        $query  = $this->user->tags();
        $return = [];
        Log::debug('Going to build tag-cloud');
        if (!is_null($year)) {
            Log::debug(sprintf('Year is not null: %d', $year));
            $start = $year . '-01-01';
            $end   = $year . '-12-31';
            $query->where('date', '>=', $start)->where('date', '<=', $end);
        }
        if (is_null($year)) {
            $query->whereNull('date');
            Log::debug('Year is NULL');
        }
        $tags      = $query->orderBy('id', 'desc')->get();
        $temporary = [];
        Log::debug(sprintf('Found %d tags', $tags->count()));
        /** @var Tag $tag */
        foreach ($tags as $tag) {

            $amount      = floatval($this->sumOfTag($tag, null, null));
            $min         = $amount < $min || is_null($min) ? $amount : $min;
            $max         = $amount > $max ? $amount : $max;
            $temporary[] = [
                'amount' => $amount,
                'tag'    => $tag,
            ];
            Log::debug(sprintf('Now working on tag %s with total amount %s', $tag->tag, $amount));
            Log::debug(sprintf('Minimum is now %f, maximum is %f', $min, $max));
        }
        /** @var array $entry */
        foreach ($temporary as $entry) {
            $scale          = $this->cloudScale([12, 20], $entry['amount'], $min, $max);
            $tagId          = $entry['tag']->id;
            $return[$tagId] = [
                'scale' => $scale,
                'tag'   => $entry['tag'],
            ];
        }

        Log::debug('DONE with tagcloud');

        return $return;
    }


    /**
     * @param Tag   $tag
     * @param array $data
     *
     * @return Tag
     */
    public function update(Tag $tag, array $data): Tag
    {
        $tag->tag         = $data['tag'];
        $tag->date        = $data['date'];
        $tag->description = $data['description'];
        $tag->latitude    = $data['latitude'];
        $tag->longitude   = $data['longitude'];
        $tag->zoomLevel   = $data['zoomLevel'];
        $tag->save();

        return $tag;
    }

    /**
     * @param array $range
     * @param float $amount
     * @param float $min
     * @param float $max
     *
     * @return int
     */
    private function cloudScale(array $range, float $amount, float $min, float $max): int
    {
        Log::debug(sprintf('Now in cloudScale with %s as amount and %f min, %f max', $amount, $min, $max));
        $amountDiff = $max - $min;
        Log::debug(sprintf('AmountDiff is %f', $amountDiff));

        // no difference? Every tag same range:
        if($amountDiff === 0.0) {
            Log::debug(sprintf('AmountDiff is zero, return %d', $range[0]));
            return $range[0];
        }

        $diff       = $range[1] - $range[0];
        $step       = 1;
        if ($diff != 0) {
            $step = $amountDiff / $diff;
        }
        if ($step == 0) {
            $step = 1;
        }
        $extra = round($amount / $step);


        return intval($range[0] + $extra);
    }
}
