<?php

declare(strict_types=1);

namespace FireflyIII\View\Components\Form\Alpine;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

final class TransactionCurrency extends Component
{
    public string $value;

    /**
     * Create a new component instance.
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * Get the view / contents that represent the component.
     */
    #[\Override]
    public function render(): Closure|string|View
    {
        return view('components.form.alpine.transaction-currency');
    }
}
