<?php

namespace FireflyIII\View\Components\lists;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class Accounts extends Component
{
    public LengthAwarePaginator|null $accounts = null;
    public string $objectType = '';
    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.lists.accounts');
    }
}
