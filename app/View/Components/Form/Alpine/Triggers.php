<?php

declare(strict_types=1);


namespace FireflyIII\View\Components\Form\Alpine;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Triggers extends Component
{
    public string $value;
    public string $multiple = '';
    /**
     * Create a new component instance.
     */
    public function __construct(string $value, string $multiple = '')
    {
        $this->value= $value;
        $this->multiple = $multiple;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.form.alpine.triggers');
    }
}
