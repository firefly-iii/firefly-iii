<?php

declare(strict_types=1);

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
    public function __construct(
        public Carbon $start,
        public Carbon $end
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): Closure|string|View
    {
        return view('components.dashboard.boxes');
    }
}
