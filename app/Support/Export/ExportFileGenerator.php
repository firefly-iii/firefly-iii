<?php
/**
 * ExportFileGenerator.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Support\Export;

use Carbon\Carbon;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use League\Csv\Writer;

/**
 * Class ExportFileGenerator
 */
class ExportFileGenerator
{
    /** @var Carbon */
    private $end;
    /** @var bool */
    private $exportTransactions;
    /** @var Carbon */
    private $start;

    public function __construct()
    {
        $this->start = new Carbon;
        $this->start->subYear();
        $this->end                = new Carbon;
        $this->exportTransactions = true;
    }

    /**
     * @return array
     */
    public function export(): array
    {
        $return = [];
        if ($this->exportTransactions) {
            $return['transactions'] = $this->exportTransactions();
        }

        return $return;
    }

    /**
     * @param Carbon $end
     */
    public function setEnd(Carbon $end): void
    {
        $this->end = $end;
    }

    /**
     * @param bool $exportTransactions
     */
    public function setExportTransactions(bool $exportTransactions): void
    {
        $this->exportTransactions = $exportTransactions;
    }

    /**
     * @param Carbon $start
     */
    public function setStart(Carbon $start): void
    {
        $this->start = $start;
    }

    /**
     * @return string
     */
    private function exportTransactions(): string
    {
        // TODO better place for keys?
        $header = [
            'user_id',
            'group_id',
            'journal_id',
            'created_at',
            'updated_at',
            'group_title',
            'type',
            'amount',
            'foreign_amount',
            'currency_code',
            'foreign_currency_code',
            'description',
            'date',
            'source_name',
            'source_iban',
            'source_type',
            'destination_name',
            'destination_iban',
            'destination_type',
            'reconciled',
            'category',
            'budget',
            'bill',
            'tags',
        ];

        $collector = app(GroupCollectorInterface::class);
        $collector->setRange($this->start, $this->end)->withAccountInformation()->withCategoryInformation()->withBillInformation()
                  ->withBudgetInformation();
        $journals = $collector->getExtractedJournals();

        $records = [];
        /** @var array $journal */
        foreach ($journals as $journal) {
            $records[] = [
                $journal['user_id'],
                $journal['transaction_group_id'],
                $journal['transaction_journal_id'],
                $journal['created_at']->toAtomString(),
                $journal['updated_at']->toAtomString(),
                $journal['transaction_group_title'],
                $journal['transaction_type_type'],
                $journal['amount'],
                $journal['foreign_amount'],
                $journal['currency_code'],
                $journal['foreign_currency_code'],
                $journal['description'],
                $journal['date']->toAtomString(),
                $journal['source_account_name'],
                $journal['source_account_iban'],
                $journal['source_account_type'],
                $journal['destination_account_name'],
                $journal['destination_account_iban'],
                $journal['destination_account_type'],
                $journal['reconciled'],
                $journal['category_name'],
                $journal['budget_name'],
                $journal['bill_name'],
                implode(',', $journal['tags']),
            ];
        }

        //load the CSV document from a string
        $csv = Writer::createFromString('');

        //insert the header
        $csv->insertOne($header);

        //insert all the records
        $csv->insertAll($records);

        return $csv->getContent(); //returns the CSV document as a string
    }

}
