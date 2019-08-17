<?php
/**
 * MappedValuesValidator.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

namespace FireflyIII\Support\Import\Routine\File;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use Log;

/**
 * Class MappedValuesValidator
 */
class MappedValuesValidator
{
    /** @var AccountRepositoryInterface */
    private $accountRepos;
    /** @var BillRepositoryInterface */
    private $billRepos;
    /** @var BudgetRepositoryInterface */
    private $budgetRepos;
    /** @var CategoryRepositoryInterface */
    private $catRepos;
    /** @var CurrencyRepositoryInterface */
    private $currencyRepos;
    /** @var ImportJob */
    private $importJob;
    /** @var ImportJobRepositoryInterface */
    private $repository;

    /**
     * @param ImportJob $importJob
     */
    public function setImportJob(ImportJob $importJob): void
    {
        $this->importJob = $importJob;

        $this->repository    = app(ImportJobRepositoryInterface::class);
        $this->accountRepos  = app(AccountRepositoryInterface::class);
        $this->currencyRepos = app(CurrencyRepositoryInterface::class);
        $this->billRepos     = app(BillRepositoryInterface::class);
        $this->budgetRepos   = app(BudgetRepositoryInterface::class);
        $this->catRepos      = app(CategoryRepositoryInterface::class);

        $this->repository->setUser($importJob->user);
        $this->accountRepos->setUser($importJob->user);
        $this->currencyRepos->setUser($importJob->user);
        $this->billRepos->setUser($importJob->user);
        $this->budgetRepos->setUser($importJob->user);
        $this->catRepos->setUser($importJob->user);
    }


    /**
     * @param array $mappings
     *
     * @return array
     * @throws FireflyException
     *
     */
    public function validate(array $mappings): array
    {
        $return = [];
        Log::debug('Now in validateMappedValues()');
        foreach ($mappings as $role => $values) {
            Log::debug(sprintf('Now at role "%s"', $role));
            $values = array_unique($values);
            if (count($values) > 0) {
                switch ($role) {
                    default:
                        throw new FireflyException(sprintf('Cannot validate mapped values for role "%s"', $role)); // @codeCoverageIgnore
                    case 'opposing-id':
                    case 'account-id':
                        $set           = $this->accountRepos->getAccountsById($values);
                        $valid         = $set->pluck('id')->toArray();
                        $return[$role] = $valid;
                        break;
                    case 'currency-id':
                    case 'foreign-currency-id':
                        $set           = $this->currencyRepos->getByIds($values);
                        $valid         = $set->pluck('id')->toArray();
                        $return[$role] = $valid;
                        break;
                    case 'bill-id':
                        $set           = $this->billRepos->getByIds($values);
                        $valid         = $set->pluck('id')->toArray();
                        $return[$role] = $valid;
                        break;
                    case 'budget-id':
                        $set           = $this->budgetRepos->getByIds($values);
                        $valid         = $set->pluck('id')->toArray();
                        $return[$role] = $valid;
                        break;
                    case 'category-id':
                        Log::debug('Going to validate these category ids: ', $values);
                        $set           = $this->catRepos->getByIds($values);
                        $valid         = $set->pluck('id')->toArray();
                        $return[$role] = $valid;
                        Log::debug('Valid category IDs are: ', $valid);
                        break;
                }
            }
        }

        return $return;
    }
}
