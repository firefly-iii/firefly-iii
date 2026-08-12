<?php


/*
 * GroupsLarge.php
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
use FireflyIII\Models\Account;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class GroupsLarge extends Component
{
    public Collection|LengthAwarePaginator $groups;
    public ?Account $account;
    public bool $showCategory;
    public bool $showBudget;

    /**
     * Create a new component instance.
     */
    public function __construct(Collection|LengthAwarePaginator $groups, ?bool $showCategory, ?bool $showBudget, ?Account $account = null)
    {
        $this->groups       = $groups;
        $this->account      = $account;
        $this->showCategory = $showCategory ?? false;
        $this->showBudget   = $showBudget ?? false;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): Closure|string|View
    {
        return view('components.lists.groups-large');
    }
}
