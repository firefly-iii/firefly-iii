<?php

/**
 * ReportFormRequest.php
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

namespace FireflyIII\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Carbon\Carbon;
use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\Support\Request\ChecksLogin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

use function Safe\preg_match;

/**
 * Class CategoryFormRequest.
 */
class ReportFormRequest extends FormRequest
{
    use ChecksLogin;

    /**
     * Validate list of accounts.
     */
    public function getAccountList(): Collection
    {
        // fixed
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        $set        = $this->get('accounts');
        $collection = new Collection();
        if (is_array($set)) {
            foreach ($set as $accountId) {
                $account = $repository->find((int) $accountId);
                if (null !== $account) {
                    $collection->push($account);
                }
            }
        }

        return $collection;
    }

    /**
     * Validate list of budgets.
     */
    public function getBudgetList(): Collection
    {
        /** @var BudgetRepositoryInterface $repository */
        $repository = app(BudgetRepositoryInterface::class);
        $set        = $this->get('budget');
        $collection = new Collection();
        if (is_array($set)) {
            foreach ($set as $budgetId) {
                $budget = $repository->find((int) $budgetId);
                if (null !== $budget) {
                    $collection->push($budget);
                }
            }
        }

        return $collection;
    }

    /**
     * Validate list of categories.
     */
    public function getCategoryList(): Collection
    {
        /** @var CategoryRepositoryInterface $repository */
        $repository = app(CategoryRepositoryInterface::class);
        $set        = $this->get('category');
        $collection = new Collection();
        if (is_array($set)) {
            foreach ($set as $categoryId) {
                $category = $repository->find((int) $categoryId);
                if (null !== $category) {
                    $collection->push($category);
                }
            }
        }

        return $collection;
    }

    /**
     * Validate list of accounts which exist twice in system.
     */
    public function getDoubleList(): Collection
    {
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        $set        = $this->get('double');
        $collection = new Collection();
        if (is_array($set)) {
            foreach ($set as $accountId) {
                $account = $repository->find((int) $accountId);
                if (null !== $account) {
                    $collection->push($account);
                }
            }
        }

        return $collection;
    }

    /**
     * Validate end date.
     *
     * @throws FireflyException
     */
    public function getEndDate(): Carbon
    {
        $date  = today(config('app.timezone'));
        $range = $this->get('daterange');
        $parts = explode(' - ', (string) $range);
        if (2 === count($parts)) {
            $string  = $parts[1];
            // validate as date
            // if regex for YYYY-MM-DD:
            $pattern = '/^(19|20)\d\d-(0[1-9]|1[012])-(0[1-9]|[12][\d]|3[01])$/';
            $result  = preg_match($pattern, $string);
            if (false !== $result && 0 !== $result) {
                try {
                    $date = new Carbon($parts[1]);
                } catch (Exception $e) { // intentional generic exception
                    $error = sprintf('"%s" is not a valid date range: %s', $range, $e->getMessage());
                    app('log')->error($error);
                    app('log')->error($e->getTraceAsString());

                    throw new FireflyException($error, 0, $e);
                }

                return $date;
            }
            $error   = sprintf('"%s" is not a valid date range: %s', $range, 'invalid format :(');
            app('log')->error($error);

            throw new FireflyException($error, 0);
        }

        return $date;
    }

    /**
     * Validate start date.
     *
     * @throws FireflyException
     */
    public function getStartDate(): Carbon
    {
        $date  = today(config('app.timezone'));
        $range = $this->get('daterange');
        $parts = explode(' - ', (string) $range);
        if (2 === count($parts)) {
            $string  = $parts[0];
            // validate as date
            // if regex for YYYY-MM-DD:
            $pattern = '/^(19|20)\d\d-(0[1-9]|1[012])-(0[1-9]|[12][\d]|3[01])$/';
            $result  = preg_match($pattern, $string);
            if (false !== $result && 0 !== $result) {
                try {
                    $date = new Carbon($parts[0]);
                } catch (Exception $e) { // intentional generic exception
                    $error = sprintf('"%s" is not a valid date range: %s', $range, $e->getMessage());
                    app('log')->error($error);
                    app('log')->error($e->getTraceAsString());

                    throw new FireflyException($error, 0, $e);
                }

                return $date;
            }
            $error   = sprintf('"%s" is not a valid date range: %s', $range, 'invalid format :(');
            app('log')->error($error);

            throw new FireflyException($error, 0);
        }

        return $date;
    }

    /**
     * Validate list of tags.
     */
    public function getTagList(): Collection
    {
        /** @var TagRepositoryInterface $repository */
        $repository = app(TagRepositoryInterface::class);
        $set        = $this->get('tag');
        $collection = new Collection();
        if (is_array($set)) {
            app('log')->debug('Set is:', $set);
        }
        if (!is_array($set)) {
            app('log')->debug(sprintf('Set is not an array! "%s"', $set));

            return $collection;
        }
        foreach ($set as $tagTag) {
            app('log')->debug(sprintf('Now searching for "%s"', $tagTag));
            $tag = $repository->findByTag($tagTag);
            if (null !== $tag) {
                $collection->push($tag);

                continue;
            }
            $tag = $repository->find((int) $tagTag);
            if (null !== $tag) {
                $collection->push($tag);
            }
        }

        return $collection;
    }

    /**
     * Rules for this request.
     */
    public function rules(): array
    {
        return [
            'report_type' => 'in:audit,default,category,budget,tag,double',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        if ($validator->fails()) {
            Log::channel('audit')->error(sprintf('Validation errors in %s', self::class), $validator->errors()->toArray());
        }
    }
}
