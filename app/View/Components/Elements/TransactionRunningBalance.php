<?php

/*
 * TransactionRunningBalance.php
 * Copyright (c) 2026 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

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
