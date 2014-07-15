<?php

namespace Firefly\Storage\Budget;


interface BudgetRepositoryInterface
{
    public function getAsSelectList();
    public function find($id);

} 