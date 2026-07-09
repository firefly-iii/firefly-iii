<?php

namespace FireflyIII\View\Components\Lists;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class Ale extends Component
{
    public array|Collection $logEntries;
    /**
     * Create a new component instance.
     */
    public function __construct(array|Collection $logEntries)
    {
        $this->logEntries = $logEntries;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.lists.ale');
    }
}
