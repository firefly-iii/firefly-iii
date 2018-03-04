<?php
/**
 * Roles.php
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

namespace FireflyIII\Support\Import\Configuration\File;

use FireflyIII\Import\Specifics\SpecificInterface;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Import\Configuration\ConfigurationInterface;
use League\Csv\Reader;
use League\Csv\Statement;
use Log;

/**
 * Class Roles.
 */
class Roles implements ConfigurationInterface
{
    /**
     * @var array
     */
    private $data = [];
    /** @var ImportJob */
    private $job;
    /** @var ImportJobRepositoryInterface */
    private $repository;
    /** @var string */
    private $warning = '';

    /**
     * Get the data necessary to show the configuration screen.
     *
     * @return array
     *
     * @throws \League\Csv\Exception
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function getData(): array
    {
        $content = $this->repository->uploadFileContents($this->job);
        $config  = $this->getConfig();
        $headers = [];

        // create CSV reader.
        $reader = Reader::createFromString($content);
        $reader->setDelimiter($config['delimiter']);

        if ($config['has-headers']) {
            // get headers:
            $reader->setHeaderOffset(0);
            $headers = $reader->getHeader();
        }

        // example rows:
        $stmt = (new Statement)->limit(intval(config('csv.example_rows', 5)))->offset(0);
        // set data:
        $roles = $this->getRoles();
        asort($roles);
        $this->data = [
            'examples' => [],
            'roles'    => $roles,
            'total'    => 0,
            'headers'  => $headers,
        ];

        $records = $stmt->process($reader);
        foreach ($records as $row) {
            $row                 = array_values($row);
            $row                 = $this->processSpecifics($row);
            $count               = count($row);
            $this->data['total'] = $count > $this->data['total'] ? $count : $this->data['total'];
            $this->processRow($row);
        }

        $this->updateColumCount();
        $this->makeExamplesUnique();

        return $this->data;
    }

    /**
     * Return possible warning to user.
     *
     * @return string
     */
    public function getWarningMessage(): string
    {
        return $this->warning;
    }

    /**
     * @param ImportJob $job
     *
     * @return ConfigurationInterface
     */
    public function setJob(ImportJob $job): ConfigurationInterface
    {
        $this->job        = $job;
        $this->repository = app(ImportJobRepositoryInterface::class);
        $this->repository->setUser($job->user);

        return $this;
    }

    /**
     * Store the result.
     *
     * @param array $data
     *
     * @return bool
     */
    public function storeConfiguration(array $data): bool
    {
        Log::debug('Now in storeConfiguration of Roles.');
        $config = $this->getConfig();
        $count  = $config['column-count'];
        for ($i = 0; $i < $count; ++$i) {
            $role                            = $data['role'][$i] ?? '_ignore';
            $mapping                         = isset($data['map'][$i]) && $data['map'][$i] === '1' ? true : false;
            $config['column-roles'][$i]      = $role;
            $config['column-do-mapping'][$i] = $mapping;
            Log::debug(sprintf('Column %d has been given role %s (mapping: %s)', $i, $role, var_export($mapping, true)));
        }

        $this->saveConfig($config);
        $this->ignoreUnmappableColumns();
        $res = $this->isRolesComplete();
        if ($res === true) {
            $config          = $this->getConfig();
            $config['stage'] = 'map';
            $this->saveConfig($config);
            $this->isMappingNecessary();
        }

        return true;
    }

    /**
     * Short hand method.
     *
     * @return array
     */
    private function getConfig(): array
    {
        return $this->repository->getConfiguration($this->job);
    }

    /**
     * @return array
     */
    private function getRoles(): array
    {
        $roles = [];
        foreach (array_keys(config('csv.import_roles')) as $role) {
            $roles[$role] = trans('import.column_' . $role);
        }

        return $roles;
    }

    /**
     * @return bool
     */
    private function ignoreUnmappableColumns(): bool
    {
        $config = $this->getConfig();
        $count  = $config['column-count'];
        for ($i = 0; $i < $count; ++$i) {
            $role    = $config['column-roles'][$i] ?? '_ignore';
            $mapping = $config['column-do-mapping'][$i] ?? false;
            Log::debug(sprintf('Role for column %d is %s, and mapping is %s', $i, $role, var_export($mapping, true)));

            if ('_ignore' === $role && true === $mapping) {
                $mapping = false;
                Log::debug(sprintf('Column %d has type %s so it cannot be mapped.', $i, $role));
            }
            $config['column-do-mapping'][$i] = $mapping;
        }
        $this->saveConfig($config);

        return true;
    }

    /**
     * @return bool
     */
    private function isMappingNecessary()
    {
        $config     = $this->getConfig();
        $count      = $config['column-count'];
        $toBeMapped = 0;
        for ($i = 0; $i < $count; ++$i) {
            $mapping = $config['column-do-mapping'][$i] ?? false;
            if (true === $mapping) {
                ++$toBeMapped;
            }
        }
        Log::debug(sprintf('Found %d columns that need mapping.', $toBeMapped));
        if (0 === $toBeMapped) {
            $config['stage'] = 'ready';
        }

        $this->saveConfig($config);

        return true;
    }

    /**
     * @return bool
     */
    private function isRolesComplete(): bool
    {
        $config   = $this->getConfig();
        $count    = $config['column-count'];
        $assigned = 0;

        // check if data actually contains amount column (foreign amount does not count)
        $hasAmount        = false;
        $hasForeignAmount = false;
        $hasForeignCode   = false;
        for ($i = 0; $i < $count; ++$i) {
            $role = $config['column-roles'][$i] ?? '_ignore';
            if ('_ignore' !== $role) {
                ++$assigned;
            }
            if (in_array($role, ['amount', 'amount_credit', 'amount_debit'])) {
                $hasAmount = true;
            }
            if ($role === 'foreign-currency-code') {
                $hasForeignCode = true;
            }
            if ($role === 'amount_foreign') {
                $hasForeignAmount = true;
            }
        }
        if ($assigned > 0 && $hasAmount && ($hasForeignCode === false && $hasForeignAmount === false)) {
            $this->warning = '';
            $this->saveConfig($config);

            return true;
        }
        // warn if has foreign amount but no currency code:
        if ($hasForeignAmount && !$hasForeignCode) {
            $this->warning = strval(trans('import.foreign_amount_warning'));

            return false;
        }

        if (0 === $assigned || !$hasAmount) {
            $this->warning = strval(trans('import.roles_warning'));

            return false;
        }

        return false;
    }

    /**
     * make unique example data.
     */
    private function makeExamplesUnique(): bool
    {
        foreach ($this->data['examples'] as $index => $values) {
            $this->data['examples'][$index] = array_unique($values);
        }

        return true;
    }

    /**
     * @param array $row
     *
     * @return bool
     */
    private function processRow(array $row): bool
    {
        foreach ($row as $index => $value) {
            $value = trim($value);
            if (strlen($value) > 0) {
                $this->data['examples'][$index][] = $value;
            }
        }

        return true;
    }

    /**
     * run specifics here:
     * and this is the point where the specifix go to work.
     *
     * @param array $row
     *
     * @return array
     */
    private function processSpecifics(array $row): array
    {
        $config    = $this->getConfig();
        $specifics = $config['specifics'] ?? [];
        $names     = array_keys($specifics);
        foreach ($names as $name) {
            /** @var SpecificInterface $specific */
            $specific = app('FireflyIII\Import\Specifics\\' . $name);
            $row      = $specific->run($row);
        }

        return $row;
    }

    /**
     * @param array $array
     */
    private function saveConfig(array $array)
    {
        $this->repository->setConfiguration($this->job, $array);
    }

    /**
     * @return bool
     */
    private function updateColumCount(): bool
    {
        $config                 = $this->getConfig();
        $count                  = $this->data['total'];
        $config['column-count'] = $count;
        $this->saveConfig($config);

        return true;
    }
}
