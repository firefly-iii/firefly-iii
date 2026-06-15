<?php

namespace FireflyIII\View\Components\Lists;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Periods extends Component
{

    public array $periods;
    /**
     * Create a new component instance.
     */
    public function __construct(array $periods)
    {
        $this->periods =$periods;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.lists.periods');
    }
}
