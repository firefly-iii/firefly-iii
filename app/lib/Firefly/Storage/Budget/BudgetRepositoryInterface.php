<?php

namespace Firefly\Storage\Budget;


interface BudgetRepositoryInterface
{
    public function getAsSelectList();
    public function get();

    public function store($data);

    public function find($id);

} 