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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Repositories\Tag;

use Carbon\Carbon;
use DB;
use FireflyIII\Factory\TagFactory;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionType;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Log;

/**
 * Class TagRepository.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TagRepository implements TagRepositoryInterface
{
    /** @var User */
    private $user;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
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
     * @throws \Exception
     */
    public function destroy(Tag $tag): bool
    {
        $tag->delete();

        return true;
    }

    /**
     * @param Tag $tag
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return string
     */
    public function earnedInPeriod(Tag $tag, Carbon $start, Carbon $end): string
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);

        $collector->setUser($this->user);
        $collector->setRange($start, $end)->setTypes([TransactionType::DEPOSIT])->setTag($tag);

        return $collector->getSum();
    }

    /**
     * @param Tag $tag
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     */
    public function expenseInPeriod(Tag $tag, Carbon $start, Carbon $end): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);

        $collector->setUser($this->user);
        $collector->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL])->setTag($tag);

        return $collector->getExtractedJournals();
    }

    /**
     * @param string $tag
     *
     * @return Tag|null
     */
    public function findByTag(string $tag): ?Tag
    {
        return $this->user->tags()->where('tag', $tag)->first();
    }

    /**
     * @param int $tagId
     *
     * @return Tag|null
     */
    public function findNull(int $tagId): ?Tag
    {
        return $this->user->tags()->find($tagId);
    }

    /**
     * @param Tag $tag
     *
     * @return Carbon|null
     */
    public function firstUseDate(Tag $tag): ?Carbon
    {
        $journal = $tag->transactionJournals()->orderBy('date', 'ASC')->first();
        if (null !== $journal) {
            return $journal->date;
        }

        return null;
    }

    /**
     * @return Collection
     */
    public function get(): Collection
    {
        /** @var Collection $tags */
        $tags = $this->user->tags()->orderBy('tag', 'ASC')->get();

        return $tags;
    }

    /**
     * @param Tag $tag
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     */
    public function incomeInPeriod(Tag $tag, Carbon $start, Carbon $end): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);

        $collector->setUser($this->user);
        $collector->setRange($start, $end)->setTypes([TransactionType::DEPOSIT])->setTag($tag);

        return $collector->getExtractedJournals();
    }

    /**
     * @param Tag $tag
     *
     * @return Carbon|null
     */
    public function lastUseDate(Tag $tag): ?Carbon
    {
        $journal = $tag->transactionJournals()->orderBy('date', 'DESC')->first();
        if (null !== $journal) {
            return $journal->date;
        }

        return null;
    }

    /**
     * Will return the newest tag (if known) or NULL.
     *
     * @return Tag|null
     */
    public function newestTag(): ?Tag
    {
        return $this->user->tags()->whereNotNull('date')->orderBy('date', 'DESC')->first();
    }

    /**
     * @return Tag
     */
    public function oldestTag(): ?Tag
    {
        return $this->user->tags()->whereNotNull('date')->orderBy('date', 'ASC')->first();
    }

    /**
     * Search the users tags.
     *
     * @param string $query
     *
     * @return Collection
     */
    public function searchTags(string $query): Collection
    {
        /** @var Collection $tags */
        $tags = $this->user->tags()->orderBy('tag', 'ASC');
        if ('' !== $query) {
            $search = sprintf('%%%s%%', $query);
            $tags->where('tag', 'LIKE', $search);
        }

        return $tags->get();
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @param Tag $tag
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return string
     */
    public function spentInPeriod(Tag $tag, Carbon $start, Carbon $end): string
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);

        $collector->setUser($this->user);
        $collector->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL])->setTag($tag);

        return $collector->getSum();
    }

    /**
     * @param array $data
     *
     * @return Tag
     */
    public function store(array $data): Tag
    {
        /** @var TagFactory $factory */
        $factory = app(TagFactory::class);
        $factory->setUser($this->user);

        return $factory->create($data);
    }

    /**
     * @param Tag $tag
     * @param Carbon|null $start
     * @param Carbon|null $end
     *
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function sumsOfTag(Tag $tag, ?Carbon $start, ?Carbon $end): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);

        if (null !== $start && null !== $end) {
            $collector->setRange($start, $end);
        }

        $collector->setTag($tag)->withAccountInformation();
        $journals = $collector->getExtractedJournals();

        $sums = [
            TransactionType::WITHDRAWAL => '0',
            TransactionType::DEPOSIT    => '0',
            TransactionType::TRANSFER   => '0',
        ];

        /** @var array $journal */
        foreach ($journals as $journal) {
            $amount = app('steam')->positive((string)$journal['amount']);
            $type   = $journal['transaction_type_type'];
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
        $tags          = $this->getTagsInYear($year);
        $max           = $this->getMaxAmount($tags);
        $min           = $this->getMinAmount($tags);
        $diff          = bcsub($max, $min);
        $return        = [];
        $minimumFont   = '12'; // default scale is from 12 to 24, so 12 points.
        $maxPoints     = '12';
        $pointsPerCoin = '0';

        Log::debug(sprintf('Minimum is %s, maximum is %s, difference is %s', $min, $max, $diff));

        if (0 !== bccomp($diff, '0')) { // for each full coin in tag, add so many points
            // minus the smallest tag.
            $pointsPerCoin = bcdiv($maxPoints, $diff);
        }

        Log::debug(sprintf('Each coin in a tag earns it %s points', $pointsPerCoin));
        /** @var Tag $tag */
        foreach ($tags as $tag) {
            $amount       = (string)$tag->amount_sum;
            $amount       = '' === $amount ? '0' : $amount;
            $amountMin = bcsub($amount, $min);
            $pointsForTag = bcmul($amountMin, $pointsPerCoin);
            $fontSize     = bcadd($minimumFont, $pointsForTag);
            Log::debug(sprintf('Tag "%s": Amount is %s, so points is %s', $tag->tag, $amount, $fontSize));

            // return value for tag cloud:
            $return[$tag->id] = [
                'size' => $fontSize,
                'tag'  => $tag->tag,
                'id'   => $tag->id,
            ];
        }

        return $return;
    }

    /**
     * @param int|null $year
     *
     * @return Collection
     */
    private function getTagsInYear(?int $year): Collection
    {
        // get all tags in the year (if present):
        $tagQuery = $this->user->tags()
                               ->leftJoin('tag_transaction_journal', 'tag_transaction_journal.tag_id', '=', 'tags.id')
                               ->leftJoin('transaction_journals', 'tag_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
                               ->leftJoin('transactions', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                               ->where(
                                   function (Builder $query) {
                                       $query->where('transactions.amount', '>', 0);
                                       $query->orWhereNull('transactions.amount');
                                   }
                               )
                               ->groupBy(['tags.id', 'tags.tag']);

        // add date range (or not):
        if (null === $year) {
            Log::debug('Get tags without a date.');
            $tagQuery->whereNull('tags.date');
        }
        if (null !== $year) {
            Log::debug(sprintf('Get tags with year %s.', $year));
            $tagQuery->where('tags.date', '>=', $year . '-01-01 00:00:00')->where('tags.date', '<=', $year . '-12-31 23:59:59');
        }

        return $tagQuery->get(['tags.id', 'tags.tag', DB::raw('SUM(transactions.amount) as amount_sum')]);

    }

    /**
     * @param Collection $tags
     *
     * @return string
     */
    private function getMaxAmount(Collection $tags): string
    {
        $max = '0';
        /** @var Tag $tag */
        foreach ($tags as $tag) {
            $amount = (string)$tag->amount_sum;
            $amount = '' === $amount ? '0' : $amount;
            $max    = 1 === bccomp($amount, $max) ? $amount : $max;

        }
        Log::debug(sprintf('Maximum is %s.', $max));

        return $max;
    }

    /**
     * @param Collection $tags
     *
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function getMinAmount(Collection $tags): string
    {
        $min = null;

        /** @var Tag $tag */
        foreach ($tags as $tag) {
            $amount = (string)$tag->amount_sum;
            $amount = '' === $amount ? '0' : $amount;

            if (null === $min) {
                $min = $amount;
            }
            $min = -1 === bccomp($amount, $min) ? $amount : $min;
        }


        if (null === $min) {
            $min = '0';
        }
        Log::debug(sprintf('Minimum is %s.', $min));

        return $min;
    }

    /**
     * @param Tag $tag
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     */
    public function transferredInPeriod(Tag $tag, Carbon $start, Carbon $end): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setUser($this->user);
        $collector->setRange($start, $end)->setTypes([TransactionType::TRANSFER])->setTag($tag);

        return $collector->getExtractedJournals();
    }

    /**
     * @param Tag $tag
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
        $tag->zoomLevel   = $data['zoom_level'];
        $tag->save();

        return $tag;
    }
}
