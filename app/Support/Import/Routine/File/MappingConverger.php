<?php
/**
 * MappingConverger.php
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
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Import\Placeholder\ColumnValue;
use Log;

/**
 * Class MappingConverger
 */
class MappingConverger
{
    /** @var array */
    private $doMapping;
    /** @var ImportJob */
    private $importJob;
    /** @var array */
    private $mappedValues;
    /** @var array */
    private $mapping;
    /** @var ImportJobRepositoryInterface */
    private $repository;
    /** @var array */
    private $roles;

    /**
     * Each cell in the CSV file could be linked to a mapped value. This depends on the role of
     * the column and the content of the cell. This method goes over all cells, and using their
     * associated role, will see if it has been linked to a mapped value. These mapped values
     * are all IDs of objects in the Firefly III database.
     *
     * If such a mapping exists the role of the cell changes to whatever the mapped value is.
     *
     * Examples:
     *
     * - Cell with content "Checking Account" and role "account-name". Mapping links "Checking Account" to account-id 2.
     * - Cell with content "Checking Account" and role "description". No mapping, so value and role remains the same.
     *
     * @param array $lines
     *
     * @return array
     * @throws FireflyException
     */
    public function converge(array $lines): array
    {
        Log::debug('Start converging process.');
        $collection = [];
        $total      = count($lines);
        /** @var array $line */
        foreach ($lines as $lineIndex => $line) {
            Log::debug(sprintf('Now converging line %d out of %d.', $lineIndex + 1, $total));
            $set          = $this->processLine($line);
            $collection[] = $set;
        }

        return $collection;

    }

    /**
     * @codeCoverageIgnore
     * @return array
     */
    public function getMappedValues(): array
    {
        return $this->mappedValues;
    }

    /**
     * @param ImportJob $importJob
     */
    public function setImportJob(ImportJob $importJob): void
    {
        $this->importJob  = $importJob;
        $this->repository = app(ImportJobRepositoryInterface::class);
        $this->repository->setUser($importJob->user);
        $this->mappedValues = [];
        $config             = $importJob->configuration;
        $this->roles        = $config['column-roles'] ?? [];
        $this->mapping      = $config['column-mapping-config'] ?? [];
        $this->doMapping    = $config['column-do-mapping'] ?? [];
    }

    /**
     * If the value in the column is mapped to a certain ID,
     * the column where this ID must be placed will change.
     *
     * For example, if you map role "budget-name" with value "groceries" to 1,
     * then that should become the budget-id. Not the name.
     *
     * @param int $column
     * @param int $mapped
     *
     * @return string
     * @throws FireflyException
     */
    private function getRoleForColumn(int $column, int $mapped): string
    {
        $role = $this->roles[$column] ?? '_ignore';
        if (0 === $mapped) {
            Log::debug(sprintf('Column #%d with role "%s" is not mapped.', $column, $role));

            return $role;
        }
        if (!(isset($this->doMapping[$column]) && true === $this->doMapping[$column])) {

            // if the mapping has been filled in already by a role with a higher priority,
            // ignore the mapping.
            Log::debug(sprintf('Column #%d ("%s") has something.', $column, $role));


            return $role;
        }
        $roleMapping = [
            'account-id'            => 'account-id',
            'account-name'          => 'account-id',
            'account-iban'          => 'account-id',
            'account-number'        => 'account-id',
            'bill-id'               => 'bill-id',
            'bill-name'             => 'bill-id',
            'budget-id'             => 'budget-id',
            'budget-name'           => 'budget-id',
            'currency-id'           => 'currency-id',
            'currency-name'         => 'currency-id',
            'currency-code'         => 'currency-id',
            'currency-symbol'       => 'currency-id',
            'category-id'           => 'category-id',
            'category-name'         => 'category-id',
            'foreign-currency-id'   => 'foreign-currency-id',
            'foreign-currency-code' => 'foreign-currency-id',
            'opposing-id'           => 'opposing-id',
            'opposing-name'         => 'opposing-id',
            'opposing-iban'         => 'opposing-id',
            'opposing-number'       => 'opposing-id',
        ];
        if (!isset($roleMapping[$role])) {
            throw new FireflyException(sprintf('Cannot indicate new role for mapped role "%s"', $role)); // @codeCoverageIgnore
        }
        $newRole = $roleMapping[$role];
        Log::debug(sprintf('Role was "%s", but because of mapping (mapped to #%d), role becomes "%s"', $role, $mapped, $newRole));

        // also store the $mapped values in a "mappedValues" array.
        $this->mappedValues[$newRole][] = $mapped;
        Log::debug(sprintf('Values mapped to role "%s" are: ', $newRole), $this->mappedValues[$newRole]);

        return $newRole;
    }

    /**
     * @param array $line
     *
     * @return array
     * @throws FireflyException
     */
    private function processLine(array $line): array
    {
        $return = [];
        foreach ($line as $columnIndex => $value) {
            $value        = trim($value);
            $originalRole = $this->roles[$columnIndex] ?? '_ignore';
            Log::debug(sprintf('Now at column #%d (%s), value "%s"', $columnIndex, $originalRole, $value));
            if ('_ignore' !== $originalRole && '' != $value) {

                // is a mapped value present?
                $mapped = $this->mapping[$columnIndex][$value] ?? 0;
                // the role might change.
                $role = $this->getRoleForColumn($columnIndex, $mapped);

                $columnValue = new ColumnValue;
                $columnValue->setValue($value);
                $columnValue->setRole($role);
                $columnValue->setMappedValue($mapped);
                $columnValue->setOriginalRole($originalRole);
                $return[] = $columnValue;
            }
            if ('' === $value) {
                Log::debug('Column skipped because value is empty.');
            }
        }
        // add a special column value for the "source"
        $columnValue = new ColumnValue;
        $columnValue->setValue(sprintf('csv-import-v%s', config('firefly.version')));
        $columnValue->setMappedValue(0);
        $columnValue->setRole('original-source');
        $return[] = $columnValue;

        return $return;
    }

}
