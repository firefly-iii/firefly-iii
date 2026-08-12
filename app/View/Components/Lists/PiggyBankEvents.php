<?php


/*
 * PiggyBankEvents.php
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

namespace FireflyIII\View\Components\Lists;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class PiggyBankEvents extends Component
{
    public Collection $events;
    public bool $showPiggyBank;

    /**
     * Create a new component instance.
     */
    public function __construct(Collection $events, bool $showPiggyBank)
    {
        $this->events        = $events;
        $this->showPiggyBank = $showPiggyBank;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): Closure|string|View
    {
        return view('components.lists.piggy-bank-events');
    }
}
