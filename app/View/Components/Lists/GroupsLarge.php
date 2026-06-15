<?php

namespace FireflyIII\View\Components\Lists;

use Closure;
use FireflyIII\Models\Account;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\Component;

class GroupsLarge extends Component
{
    public LengthAwarePaginator $groups;
    public null|Account $account;
    /**
     * Create a new component instance.
     */
    public function __construct(LengthAwarePaginator $groups, Account|null $account = null)
    {
        $this->groups = $groups;
        $this->account = $account;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.lists.groups-large');
    }
}
