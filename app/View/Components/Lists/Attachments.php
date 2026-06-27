<?php

namespace FireflyIII\View\Components\Lists;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class Attachments extends Component
{
    public Collection $attachments;
    /**
     * Create a new component instance.
     */
    public function __construct(Collection $attachments)
    {
        $this->attachments = $attachments;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.lists.attachments');
    }
}
