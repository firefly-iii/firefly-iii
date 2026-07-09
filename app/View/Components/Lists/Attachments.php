<?php

declare(strict_types=1);

namespace FireflyIII\View\Components\Lists;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class Attachments extends Component
{
    public array|Collection $attachments;

    /**
     * Create a new component instance.
     */
    public function __construct(array|Collection $attachments)
    {
        $this->attachments = $attachments;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): Closure|string|View
    {
        return view('components.lists.attachments');
    }
}
