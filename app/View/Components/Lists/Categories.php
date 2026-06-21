<?php

namespace FireflyIII\View\Components\Lists;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\Component;

class Categories extends Component
{
    public LengthAwarePaginator $categories;
    /**
     * Create a new component instance.
     */
    public function __construct(LengthAwarePaginator $categories)
    {
        $this->categories = $categories;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.lists.categories');
    }
}
