<?php
/**
 * ReportFormRequest.php
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

namespace FireflyIII\Http\Requests;

use Carbon\Carbon;
use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Class CategoryFormRequest.
 */
class ReportFormRequest extends Request
{
    /**
     * @return bool
     */
    public function authorize()
    {
        // Only allow logged in users
        return auth()->check();
    }

    /**
     * @return Collection
     */
    public function getAccountList(): Collection
    {
        // fixed
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        $set        = $this->get('accounts');
        $collection = new Collection;
        if (is_array($set)) {
            foreach ($set as $accountId) {
                $account = $repository->find(intval($accountId));
                if (null !== $account->id) {
                    $collection->push($account);
                }
            }
        }

        return $collection;
    }

    /**
     * @return Collection
     */
    public function getBudgetList(): Collection
    {
        /** @var BudgetRepositoryInterface $repository */
        $repository = app(BudgetRepositoryInterface::class);
        $set        = $this->get('budget');
        $collection = new Collection;
        if (is_array($set)) {
            foreach ($set as $budgetId) {
                $budget = $repository->find(intval($budgetId));
                if (null !== $budget->id) {
                    $collection->push($budget);
                }
            }
        }

        return $collection;
    }

    /**
     * @return Collection
     */
    public function getCategoryList(): Collection
    {
        /** @var CategoryRepositoryInterface $repository */
        $repository = app(CategoryRepositoryInterface::class);
        $set        = $this->get('category');
        $collection = new Collection;
        if (is_array($set)) {
            foreach ($set as $categoryId) {
                $category = $repository->find(intval($categoryId));
                if (null !== $category->id) {
                    $collection->push($category);
                }
            }
        }

        return $collection;
    }

    /**
     * @return Carbon
     *
     * @throws FireflyException
     */
    public function getEndDate(): Carbon
    {
        $date  = new Carbon;
        $range = $this->get('daterange');
        $parts = explode(' - ', strval($range));
        if (2 === count($parts)) {
            try {
                $date = new Carbon($parts[1]);
            } catch (Exception $e) {
                throw new FireflyException(sprintf('"%s" is not a valid date range.', $range));
            }
        }

        return $date;
    }

    /**
     * @return Carbon
     *
     * @throws FireflyException
     */
    public function getStartDate(): Carbon
    {
        $date  = new Carbon;
        $range = $this->get('daterange');
        $parts = explode(' - ', strval($range));
        if (2 === count($parts)) {
            try {
                $date = new Carbon($parts[0]);
            } catch (Exception $e) {
                throw new FireflyException(sprintf('"%s" is not a valid date range.', $range));
            }
        }

        return $date;
    }

    /**
     * @return Collection
     */
    public function getTagList(): Collection
    {
        /** @var TagRepositoryInterface $repository */
        $repository = app(TagRepositoryInterface::class);
        $set        = $this->get('tag');
        $collection = new Collection;
        if (is_array($set)) {
            foreach ($set as $tagTag) {
                $tag = $repository->findByTag($tagTag);
                if (null !== $tag->id) {
                    $collection->push($tag);
                }
            }
        }

        return $collection;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'report_type' => 'in:audit,default,category,budget,tag',
        ];
    }
}
