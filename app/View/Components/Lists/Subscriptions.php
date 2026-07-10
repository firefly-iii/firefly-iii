<?php

declare(strict_types=1);

namespace FireflyIII\View\Components\Lists;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Subscriptions extends Component
{
    public array $bills;
    public array $sums;
    public array $totals;
    public Carbon $today;

    /**
     * Create a new component instance.
     */
    public function __construct(array $bills, array $sums, array $totals, Carbon $today)
    {
        $this->bills  = $bills;
        $this->sums   = $sums;
        $this->totals = $totals;
        $this->today  = $today;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): Closure|string|View
    {
        return view('components.lists.subscriptions');
    }
}
