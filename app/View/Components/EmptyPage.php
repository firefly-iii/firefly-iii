<?php

namespace FireflyIII\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class EmptyPage extends Component
{
    public string $route;
    public string $objectType;
    public string $type;
    /**
     * Create a new component instance.
     */
    public function __construct(string $route, string $objectType, string $type)
    {
        $this->route = $route;
        $this->objectType = $objectType;
        $this->type= $type;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.empty-page');
    }
}
