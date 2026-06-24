<?php

declare(strict_types=1);

namespace FireflyIII\View\Components\Elements;

use Closure;
use FireflyIII\Models\Account;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class TransactionAmount extends Component
{
    public string $type;
    public array $amount;
    public array $foreign;
    public ?string $pcAmount;
    public ?Account $account;
    public string $sourceAccountType;

    /**
     * Create a new component instance.
     */
    public function __construct(string $type, array $amount, array $foreign, string $sourceAccountType, ?string $pcAmount, ?Account $account)
    {
        $this->type     = $type;
        $this->amount   = $amount;
        $this->foreign  = $foreign;
        $this->account  = $account;
        $this->pcAmount = $pcAmount;
        $this->sourceAccountType = $sourceAccountType;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): Closure|string|View
    {
        return view('components.elements.transaction-amount');
    }
}
