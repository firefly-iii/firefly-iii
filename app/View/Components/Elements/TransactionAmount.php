<?php

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
    public null|string $pcAmount;
    public null|Account $account;

    /**
     * Create a new component instance.
     */
    public function __construct(string $type,array $amount, array $foreign, null|string $pcAmount, null|Account $account)
    {
        $this->type = $type;
        $this->amount = $amount;
        $this->foreign = $foreign;
        $this->account = $account;
        $this->pcAmount = $pcAmount;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.elements.transaction-amount');
    }
}
