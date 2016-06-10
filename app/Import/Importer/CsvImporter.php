<?php
/**
 * CsvImporter.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Import\Importer;


use ExpandedForm;
use FireflyIII\Import\Role\Map;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\ImportJob;

/**
 * Class CsvImporter
 *
 * @package FireflyIII\Import\Importer
 */
class CsvImporter implements ImporterInterface
{
    /** @var  ImportJob */
    public $job;

    /**
     * @return bool
     */
    public function configure(): bool
    {
        // need to do nothing, for now.

        return true;
    }

    /**
     * @return array
     */
    public function getConfigurationData(): array
    {
        $crud       = app('FireflyIII\Crud\Account\AccountCrudInterface');
        $accounts   = $crud->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);
        $delimiters = [
            ','   => trans('form.csv_comma'),
            ';'   => trans('form.csv_semicolon'),
            'tab' => trans('form.csv_tab'),
        ];

        $data = [
            'accounts'   => ExpandedForm::makeSelectList($accounts),
            'specifix'   => [],
            'delimiters' => $delimiters,
        ];

        return $data;
    }

    /**
     * @param ImportJob $job
     */
    public function setJob(ImportJob $job)
    {
        $this->job = $job;
    }

    /**
     * Returns a Map thing used to allow the user to
     * define roles for each entry.
     *
     * @return Map
     */
    public function prepareRoles(): Map
    {
        return 'do not work';
        exit;
    }
}