<?php

namespace FireflyIII\View\Components\Dashboard;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Boxes extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(public Carbon $start, public Carbon $end)
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.dashboard.boxes');
    }
}
