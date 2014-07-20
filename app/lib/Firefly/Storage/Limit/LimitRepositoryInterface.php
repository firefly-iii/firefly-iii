<?php

namespace Firefly\Storage\Limit;


interface LimitRepositoryInterface
{

    public function store($data);

    public function getTJByBudgetAndDateRange(\Budget $budget, \Carbon\Carbon $start, \Carbon\Carbon $end);

    public function find($limitId);
} 