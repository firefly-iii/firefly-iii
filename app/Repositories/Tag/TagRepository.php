<?php
/**
 * TagRepository.php
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

namespace FireflyIII\Repositories\Tag;

use Carbon\Carbon;
use DB;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Helpers\Filter\InternalTransferFilter;
use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;

/**
 * Class TagRepository.
 */
class TagRepository implements TagRepositoryInterface
{
    /** @var User */
    private $user;

    /**
     * @param TransactionJournal $journal
     * @param Tag                $tag
     *
     * @return bool
     */
    public function connect(TransactionJournal $journal, Tag $tag): bool
    {
        // Already connected:
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
        if (null === $tag) {
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
        // @var Tag $tag
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
        if (null !== $journal) {
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
        if (null !== $journal) {
            return $journal->date;
        }

        return new Carbon;
    }

    /**
     * @return Tag
     */
    public function oldestTag(): ?Tag
    {
        return $this->user->tags()->whereNotNull('date')->orderBy('date', 'ASC')->first();
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

        if (null !== $start && null !== $end) {
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
     * @param Tag         $tag
     * @param Carbon|null $start
     * @param Carbon|null $end
     *
     * @return array
     */
    public function sumsOfTag(Tag $tag, ?Carbon $start, ?Carbon $end): array
    {
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);

        if (null !== $start && null !== $end) {
            $collector->setRange($start, $end);
        }

        $collector->setAllAssetAccounts()->setTag($tag)->withOpposingAccount();
        $collector->removeFilter(InternalTransferFilter::class);
        $journals = $collector->getJournals();

        $sums = [
            TransactionType::WITHDRAWAL => '0',
            TransactionType::DEPOSIT    => '0',
            TransactionType::TRANSFER   => '0',
        ];

        foreach ($journals as $journal) {
            $amount = app('steam')->positive(strval($journal->transaction_amount));
            $type   = $journal->transaction_type_type;
            if (TransactionType::WITHDRAWAL === $type) {
                $amount = bcmul($amount, '-1');
            }
            $sums[$type] = bcadd($sums[$type], $amount);
        }

        return $sums;
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
        // Some vars
        $min    = null;
        $max    = '0';
        $return = [];
        // get all tags
        $allTags = $this->user->tags();
        // get tags with a certain amount (in this range):
        $query = $this->user->tags()
                            ->leftJoin('tag_transaction_journal', 'tag_transaction_journal.tag_id', '=', 'tags.id')
                            ->leftJoin('transaction_journals', 'tag_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
                            ->leftJoin('transactions', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                            ->where('transactions.amount', '>', 0)
                            ->groupBy(['tags.id','tags.tag']);

        // add date range (or not):
        if (null === $year) {
            $query->whereNull('tags.date');
            $allTags->whereNull('date');
        }
        if (null !== $year) {
            $start = $year . '-01-01';
            $end   = $year . '-12-31';
            $query->where('tags.date', '>=', $start)->where('tags.date', '<=', $end);
            $allTags->where('date', '>=', $start)->where('date', '<=', $end);
        }
        $set             = $query->get(['tags.id', DB::raw('SUM(transactions.amount) as amount_sum')]);
        $tagsWithAmounts = [];
        /** @var Tag $tag */
        foreach ($set as $tag) {
            $tagsWithAmounts[$tag->id] = strval($tag->amount_sum);
        }

        $tags      = $query->orderBy('id', 'desc')->get(['tags.id','tags.tag']);
        $temporary = [];
        /** @var Tag $tag */
        foreach ($tags as $tag) {
            $amount = $tagsWithAmounts[$tag->id] ?? '0';
            if (null === $min) {
                $min = $amount;
            }
            $max = 1 === bccomp($amount, $max) ? $amount : $max;
            $min = bccomp($amount, $min) === -1 ? $amount : $min;

            $temporary[] = [
                'amount' => $amount,
                'tag'    => $tag,
            ];
        }
        /** @var array $entry */
        foreach ($temporary as $entry) {
            $scale          = $this->cloudScale([12, 20], floatval($entry['amount']), floatval($min), floatval($max));
            $tagId          = $entry['tag']->id;
            $return[$tagId] = [
                'scale' => $scale,
                'tag'   => $entry['tag'],
            ];
        }

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
        $amountDiff = $max - $min;

        // no difference? Every tag same range:
        if (0.0 === $amountDiff) {
            return $range[0];
        }

        $diff = $range[1] - $range[0];
        $step = 1;
        if (0 != $diff) {
            $step = $amountDiff / $diff;
        }
        if (0 == $step) {
            $step = 1;
        }
        $extra = round($amount / $step);

        return intval($range[0] + $extra);
    }
}
