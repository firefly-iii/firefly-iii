<?php

namespace FireflyIII\View\Components\Lists;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\Component;

class Subscriptions extends Component
{
    public array $bills;
    public array $sums;
    public array $totals;
    /**
     * Create a new component instance.
     */
    public function __construct(array $bills, array $sums, array $totals)
    {
        $this->bills = $bills;
        $this->sums = $sums;
        $this->totals = $totals;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.lists.subscriptions');
    }
}
