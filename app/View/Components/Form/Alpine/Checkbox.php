<?php

declare(strict_types=1);


namespace FireflyIII\View\Components\Form\Alpine;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Checkbox extends Component
{
    public string $id;
    public string $value;
    public string $title;
    /**
     * Create a new component instance.
     */
    public function __construct(string $id, string $value, string $title)
    {
        $this->id = $id;
        $this->value= $value;
        $this->title = $title;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.form.alpine.checkbox');
    }
}
