<?php

declare(strict_types=1);


namespace FireflyIII\View\Components\Lists;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class PiggyBankEvents extends Component
{
    public Collection $events;
    public bool $showPiggyBank;
    /**
     * Create a new component instance.
     */
    public function __construct(Collection $events, bool $showPiggyBank)
    {
        $this->events = $events;
        $this->showPiggyBank = $showPiggyBank;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.lists.piggy-bank-events');
    }
}
