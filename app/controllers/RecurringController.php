<?php

use Firefly\Storage\RecurringTransaction\RecurringTransactionRepositoryInterface as RTR;

class RecurringController extends BaseController
{
    protected $_repository;
    public function __construct(RTR $repository)
    {
        $this->_repository = $repository;
        View::share('menu', 'home');
    }
    public function create()
    {
    }

    public function delete()
    {
    }

    public function destroy()
    {
    }

    public function edit()
    {
    }

    public function index()
    {
        $list = $this->_repository->get();
        return View::make('recurring.index');
    }

    public function show()
    {
    }

    public function store()
    {
    }

    public function update()
    {
    }
} 