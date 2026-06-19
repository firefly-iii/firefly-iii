<?php

declare(strict_types=1);

namespace FireflyIII\View\Components\Layout;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Flash extends Component
{
    public bool $invalidMonetaryLocale;
    public string $upgradeSecurityMessage;
    public string $upgradeSecurityLevel;

    /**
     * Create a new component instance.
     */
    public function __construct(bool $invalidMonetaryLocale, string $upgradeSecurityMessage, string $upgradeSecurityLevel)
    {
        $this->invalidMonetaryLocale  = $invalidMonetaryLocale;
        $this->upgradeSecurityMessage = $upgradeSecurityMessage;
        $this->upgradeSecurityLevel   = $upgradeSecurityLevel;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): Closure|string|View
    {
        return view('components.layout.flash');
    }
}
