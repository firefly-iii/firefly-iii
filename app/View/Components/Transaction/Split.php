<?php

namespace FireflyIII\View\Components\Transaction;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Split extends Component
{
    public array $optionalFields;
    public array $optionalDateFields;

    /**
     * Create a new component instance.
     */
    public function __construct(array $optionalFields, array $optionalDateFields)
    {
        $this->optionalFields = $optionalFields;
        $this->optionalDateFields = $optionalDateFields;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.transaction.split');
    }
}
