<?php

declare(strict_types=1);


namespace FireflyIII\View\Components\Report\Partial;

use Closure;
use FireflyIII\Models\Account;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class JournalsAudit extends Component
{
    public array $journals;
    public array $auditData;
    public Account $account;
    /**
     * Create a new component instance.
     */
    public function __construct(array $journals,array $auditData, Account $account)
    {
        $this->journals = $journals;
        $this->auditData = $auditData;
        $this->account   = $account;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.report.partial.journals-audit');
    }
}
