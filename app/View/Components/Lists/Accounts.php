<?php

declare(strict_types=1);

namespace FireflyIII\View\Components\Lists;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\Component;

class Accounts extends Component
{
    public LengthAwarePaginator $accounts;
    public string $objectType = '';

    /**
     * Create a new component instance.
     */
    public function __construct(LengthAwarePaginator $accounts, string $objectType)
    {
        $this->accounts   = $accounts;
        $this->objectType = $objectType;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): Closure|string|View
    {
        return view('components.lists.accounts');
    }
}
