<?php

namespace FireflyIII\View\Components\Elements;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CardFooterWithMenu extends Component
{
    public string $route;
    public string $linkTitle;
    /**
     * Create a new component instance.
     */
    public function __construct( string $route, string $linkTitle)
    {
        $this->route = $route;
        $this->linkTitle = $linkTitle;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.elements.card-footer-with-menu');
    }
}
