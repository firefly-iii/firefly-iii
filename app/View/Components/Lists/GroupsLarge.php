<?php

namespace FireflyIII\View\Components\Lists;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\Component;

class GroupsLarge extends Component
{
    public LengthAwarePaginator $groups;
    /**
     * Create a new component instance.
     */
    public function __construct(LengthAwarePaginator $groups)
    {
        $this->groups = $groups;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.lists.groups-large');
    }
}
