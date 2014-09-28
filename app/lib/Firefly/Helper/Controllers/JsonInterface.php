<?php

namespace Firefly\Helper\Controllers;

use LaravelBook\Ardent\Builder;

/**
 * Interface JsonInterface
 *
 * @package Firefly\Helper\Controllers
 */
interface JsonInterface
{

    /**
     * Grabs all the parameters entered by the DataTables JQuery plugin and creates
     * a nice array to be used by the other methods. It's also cleaning up and what-not.
     *
     * @return array
     */
    public function dataTableParameters();

    /**
     * Do some sorting, counting and ordering on the query and return a nicely formatted array
     * that can be used by the DataTables JQuery plugin.
     *
     * @param array   $parameters
     * @param Builder $query
     *
     * @return array
     */
    public function journalDataset(array $parameters, Builder $query);

    /**
     * Builds most of the query required to grab transaction journals from the database.
     * This is useful because all three pages showing different kinds of transactions use
     * the exact same query with only slight differences.
     *
     * @param array $parameters
     *
     * @return Builder
     */
    public function journalQuery(array $parameters);

    /**
     * Do some sorting, counting and ordering on the query and return a nicely formatted array
     * that can be used by the DataTables JQuery plugin.
     *
     * @param array   $parameters
     * @param Builder $query
     *
     * @return array
     */
    public function recurringTransactionsDataset(array $parameters, Builder $query);

    /**
     * Create a query that will pick up all recurring transactions from the database.
     *
     * @param array $parameters
     *
     * @return Builder
     */
    public function recurringTransactionsQuery(array $parameters);
} 