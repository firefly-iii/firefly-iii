<?php

declare(strict_types=1);

namespace FireflyIII\View\Components\Elements;

use Closure;
use FireflyIII\Models\Account;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class TransactionRunningBalance extends Component
{
    public bool $balanceDirty;
    public array $currency;
    public array $foreign;
    public array $source;
    public array $destination;
    public string $type;
    public ?Account $account;

    /**
     * Create a new component instance.
     */
    public function __construct(
        ?bool $balanceDirty,
        array $source,
        array $destination,
        array $currency,
        array $foreign,
        string $type,
        ?Account $account
    ) {
        $this->balanceDirty = $balanceDirty ?? false;
        $this->currency     = $currency;
        $this->foreign      = $foreign;
        $this->type         = $type;
        $this->source       = $source;
        $this->destination  = $destination;
        $this->account      = $account;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): Closure|string|View
    {
        return view('components.elements.transaction-running-balance');
    }
}
