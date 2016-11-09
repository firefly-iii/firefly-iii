<?php
/**
 * ReportFormRequest.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Requests;

use Carbon\Carbon;
use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Class CategoryFormRequest
 *
 *
 * @package FireflyIII\Http\Requests
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
    public function getAccountList():Collection
    {
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        $set        = $this->get('accounts');
        $collection = new Collection;
        if (is_array($set)) {
            foreach ($set as $accountId) {
                $account = $repository->find(intval($accountId));
                if (!is_null($account->id)) {
                    $collection->push($account);
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
                if (!is_null($category->id)) {
                    $collection->push($category);
                }
            }
        }

        return $collection;
    }

    public function getEndDate(): Carbon
    {
        $date  = new Carbon;
        $range = $this->get('daterange');
        $parts = explode(' - ', strval($range));
        if (count($parts) === 2) {
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
     * @throws FireflyException
     */
    public function getStartDate(): Carbon
    {
        $date  = new Carbon;
        $range = $this->get('daterange');
        $parts = explode(' - ', strval($range));
        if (count($parts) === 2) {
            try {
                $date = new Carbon($parts[0]);
            } catch (Exception $e) {
                throw new FireflyException(sprintf('"%s" is not a valid date range.', $range));
            }
        }

        return $date;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'report_type' => 'in:audit,default,category',
        ];
    }

}
