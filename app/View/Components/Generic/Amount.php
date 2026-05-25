<?php

namespace FireflyIII\View\Components\Generic;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Amount extends Component
{

    /**
     * Create a new component instance.
     */
    public function __construct(public array $transaction)
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.generic.amount');
    }
}
