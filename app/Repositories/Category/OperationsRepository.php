<?php
/**
 * OperationsRepository.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Repositories\Category;


use Carbon\Carbon;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\Category;
use FireflyIII\Models\TransactionType;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;
use Navigation;

/**
 *
 * Class OperationsRepository
 */
class OperationsRepository implements OperationsRepositoryInterface
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
     * @param Category   $category
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function earnedInPeriod(Category $category, Collection $accounts, Carbon $start, Carbon $end): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);


        $collector->setUser($this->user);
        $collector->setRange($start, $end)->setTypes([TransactionType::DEPOSIT])->setCategory($category);

        if ($accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        // collect and group results:
        $array  = $collector->getExtractedJournals();
        $return = [];

        foreach ($array as $journal) {
            $currencyCode = $journal['currency_code'];
            if (!isset($return[$currencyCode])) {
                $return[$currencyCode] = [
                    'currency_id'             => $journal['currency_id'],
                    'currency_code'           => $journal['currency_code'],
                    'currency_name'           => $journal['currency_name'],
                    'currency_symbol'         => $journal['currency_symbol'],
                    'currency_decimal_places' => $journal['currency_decimal_places'],
                    'earned'                  => '0',
                ];
            }

            // also extract foreign currency information:
            if (null !== $journal['foreign_currency_id']) {
                $currencyCode = $journal['foreign_currency_code'];
                if (!isset($return[$currencyCode])) {
                    $return[$currencyCode] = [
                        'currency_id'             => $journal['foreign_currency_id'],
                        'currency_code'           => $journal['foreign_currency_code'],
                        'currency_name'           => $journal['foreign_currency_name'],
                        'currency_symbol'         => $journal['foreign_currency_symbol'],
                        'currency_decimal_places' => $journal['foreign_currency_decimal_places'],
                        'earned'                  => '0',
                    ];
                }
                $return[$currencyCode]['earned'] = bcadd($return[$currencyCode]['earned'], app('steam')->positive($journal['foreign_amount']));
            }
            $return[$currencyCode]['earned'] = bcadd($return[$currencyCode]['earned'], app('steam')->positive($journal['amount']));
        }

        return $return;
    }

    /**
     * @param Collection $categories
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function earnedInPeriodPerCurrency(Collection $categories, Collection $accounts, Carbon $start, Carbon $end): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);

        $collector->setUser($this->user);
        $collector->setRange($start, $end)->setTypes([TransactionType::DEPOSIT]);

        if ($categories->count() > 0) {
            $collector->setCategories($categories);
        }
        if (0 === $categories->count()) {
            $collector->setCategories($this->getCategories());
        }

        if ($accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        $journals = $collector->getExtractedJournals();
        $return   = [];
        foreach ($journals as $journal) {
            $categoryId = (int)$journal['category_id'];
            $currencyId = (int)$journal['currency_id'];
            $name       = $journal['category_name'];
            // make array for category:
            if (!isset($return[$categoryId])) {
                $return[$categoryId] = [
                    'name'   => $name,
                    'earned' => [],
                ];
            }
            if (!isset($return[$categoryId]['earned'][$currencyId])) {
                $return[$categoryId]['earned'][$currencyId] = [
                    'earned'                  => '0',
                    'currency_id'             => $currencyId,
                    'currency_symbol'         => $journal['currency_symbol'],
                    'currency_code'           => $journal['currency_code'],
                    'currency_decimal_places' => $journal['currency_decimal_places'],
                ];
            }
            $return[$categoryId]['earned'][$currencyId]['earned']
                = bcadd($return[$categoryId]['earned'][$currencyId]['earned'], $journal['amount']);
        }

        return $return;
    }

    /**
     * @param Collection $categories
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function periodExpenses(Collection $categories, Collection $accounts, Carbon $start, Carbon $end): array
    {
        $carbonFormat = Navigation::preferredCarbonFormat($start, $end);
        $data         = [];
        // prep data array:
        /** @var Category $category */
        foreach ($categories as $category) {
            $data[$category->id] = [
                'name'    => $category->name,
                'sum'     => '0',
                'entries' => [],
            ];
        }

        // get all transactions:
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);

        $collector->setAccounts($accounts)->setRange($start, $end);
        $collector->setCategories($categories)->setTypes([TransactionType::WITHDRAWAL, TransactionType::TRANSFER])
                  ->withAccountInformation()->withCategoryInformation();
        $journals = $collector->getExtractedJournals();

        // loop transactions:

        foreach ($journals as $journal) {
            $categoryId                          = (int)$journal['category_id'];
            $date                                = $journal['date']->format($carbonFormat);
            $data[$categoryId]['entries'][$date] = bcadd($data[$categoryId]['entries'][$date] ?? '0', $journal['amount']);
        }

        return $data;
    }

    /**
     * TODO not multi currency aware.
     *
     * @param Collection $categories
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function periodIncome(Collection $categories, Collection $accounts, Carbon $start, Carbon $end): array
    {
        $carbonFormat = Navigation::preferredCarbonFormat($start, $end);
        $data         = [];
        // prep data array:
        /** @var Category $category */
        foreach ($categories as $category) {
            $data[$category->id] = [
                'name'    => $category->name,
                'sum'     => '0',
                'entries' => [],
            ];
        }

        // get all transactions:
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);

        $collector->setAccounts($accounts)->setRange($start, $end);
        $collector->setCategories($categories)->setTypes([TransactionType::DEPOSIT, TransactionType::TRANSFER])
                  ->withAccountInformation();
        $journals = $collector->getExtractedJournals();

        // loop transactions:
        /** @var array $journal */
        foreach ($journals as $journal) {
            $categoryId                          = (int)$journal['category_id'];
            $date                                = $journal['date']->format($carbonFormat);
            $data[$categoryId]['entries'][$date] = bcadd($data[$categoryId]['entries'][$date] ?? '0', bcmul($journal['amount'], '-1'));
        }

        return $data;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * Returns the amount spent in a category, for a set of accounts, in a specific period.
     *
     * @param Category   $category
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function spentInPeriod(Category $category, Collection $accounts, Carbon $start, Carbon $end): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setUser($this->user);
        $collector->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL])->setCategory($category);

        if ($accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        // collect and group results:
        $array  = $collector->getExtractedJournals();
        $return = [];

        foreach ($array as $journal) {
            $currencyCode = $journal['currency_code'];
            if (!isset($return[$currencyCode])) {
                $return[$currencyCode] = [
                    'currency_id'             => $journal['currency_id'],
                    'currency_code'           => $journal['currency_code'],
                    'currency_name'           => $journal['currency_name'],
                    'currency_symbol'         => $journal['currency_symbol'],
                    'currency_decimal_places' => $journal['currency_decimal_places'],
                    'spent'                   => '0',
                ];
            }

            // also extract foreign currency information:
            if (null !== $journal['foreign_currency_id']) {
                $currencyCode = $journal['foreign_currency_code'];
                if (!isset($return[$currencyCode])) {
                    $return[$currencyCode] = [
                        'currency_id'             => $journal['foreign_currency_id'],
                        'currency_code'           => $journal['foreign_currency_code'],
                        'currency_name'           => $journal['foreign_currency_name'],
                        'currency_symbol'         => $journal['foreign_currency_symbol'],
                        'currency_decimal_places' => $journal['foreign_currency_decimal_places'],
                        'spent'                   => '0',
                    ];
                }
                $return[$currencyCode]['spent'] = bcadd($return[$currencyCode]['spent'], $journal['foreign_amount']);
            }
            $return[$currencyCode]['spent'] = bcadd($return[$currencyCode]['spent'], $journal['amount']);
        }

        return $return;
    }

    /**
     * @param Collection $categories
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function spentInPeriodPerCurrency(Collection $categories, Collection $accounts, Carbon $start, Carbon $end): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setUser($this->user);
        $collector->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL]);

        if ($categories->count() > 0) {
            $collector->setCategories($categories);
        }
        if (0 === $categories->count()) {
            $collector->setCategories($this->getCategories());
        }

        if ($accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }

        $set    = $collector->getExtractedJournals();
        $return = [];
        /** @var array $journal */
        foreach ($set as $journal) {
            $categoryId = (int)$journal['category_id'];
            $currencyId = (int)$journal['currency_id'];
            $name       = $journal['category_name'];

            // make array for category:
            if (!isset($return[$categoryId])) {
                $return[$categoryId] = [
                    'name'  => $name,
                    'spent' => [],
                ];
            }
            if (!isset($return[$categoryId]['spent'][$currencyId])) {
                $return[$categoryId]['spent'][$currencyId] = [
                    'spent'                   => '0',
                    'currency_id'             => $currencyId,
                    'currency_symbol'         => $journal['currency_symbol'],
                    'currency_code'           => $journal['currency_code'],
                    'currency_decimal_places' => $journal['currency_decimal_places'],
                ];
            }
            $return[$categoryId]['spent'][$currencyId]['spent']
                = bcadd($return[$categoryId]['spent'][$currencyId]['spent'], $journal['amount']);
        }

        return $return;
    }

    /**
     * Returns a list of all the categories belonging to a user.
     *
     * @return Collection
     */
    private function getCategories(): Collection
    {
        /** @var Collection $set */
        $set = $this->user->categories()->get();

        return $set;
    }
}