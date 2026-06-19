<?php

declare(strict_types=1);

namespace FireflyIII\View\Components\Lists;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class GroupsTiny extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public array $transactions
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): Closure|string|View
    {
        return view('components.lists.groups-tiny');
    }
}
