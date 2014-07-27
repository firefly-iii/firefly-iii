<?php
namespace Firefly\Helper\Controllers;
use Illuminate\Database\Eloquent\Collection;
/**
 * Interface BudgetInterface
 *
 * @package Firefly\Helper\Controllers
 */
interface BudgetInterface {

    /**
     * @param Collection $budgets
     *
     * @return mixed
     */
    public function organizeByDate(Collection $budgets);

} 