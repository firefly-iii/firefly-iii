<?php

declare(strict_types=1);

namespace FireflyIII\View\Components\Lists;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class PiggyBanks extends Component
{
    /**
     * Create a new component instance.
     */
    public array $piggyBanks;

    public function __construct(array $piggyBanks)
    {
        $this->piggyBanks = $piggyBanks;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): Closure|string|View
    {
        return view('components.lists.piggy-banks');
    }
}
