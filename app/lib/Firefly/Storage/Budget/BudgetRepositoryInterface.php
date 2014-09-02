<?php

namespace Firefly\Storage\Budget;

/**
 * Interface BudgetRepositoryInterface
 *
 * @package Firefly\Storage\Budget
 */
interface BudgetRepositoryInterface
{
    /**
     * @param \Budget $budget
     *
     * @return mixed
     */
    public function destroy(\Budget $budget);

    /**
     * @param $budgetId
     *
     * @return mixed
     */
    public function find($budgetId);


    /**
     * @param $budgetName
     * @return mixed
     */
    public function findByName($budgetName);

    /**
     * @return mixed
     */
    public function get();

    /**
     * @return mixed
     */
    public function getAsSelectList();

    /**
     * @param $data
     *
     * @return mixed
     */
    public function store($data);

    /**
     * @param \Budget $budget
     * @param         $data
     *
     * @return mixed
     */
    public function update(\Budget $budget, $data);

} 