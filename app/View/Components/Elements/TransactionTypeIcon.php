<?php

declare(strict_types=1);

namespace FireflyIII\View\Components\Elements;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class TransactionTypeIcon extends Component
{
    public string $type;

    /**
     * Create a new component instance.
     */
    public function __construct(string $type)
    {
        $this->type = $type;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): Closure|string|View
    {
        return view('components.elements.transaction-type-icon');
    }
}
