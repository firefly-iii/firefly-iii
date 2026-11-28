<?php

declare(strict_types=1);

namespace FireflyIII\View\Components\Elements\Alpine;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

final class PageNavigation extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct() {}

    /**
     * Get the view / contents that represent the component.
     */
    #[\Override]
    public function render(): View
    {
        return view('components.elements.alpine.page-navigation');
    }
}
