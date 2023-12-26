<?php

/*
 * RuleActionFailedOnArray.php
 * Copyright (c) 2023 james@firefly-iii.org
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

namespace FireflyIII\Events\Model\Rule;

use FireflyIII\Models\RuleAction;
use Illuminate\Queue\SerializesModels;

/**
 * Class RuleActionFailedOnArray
 */
class RuleActionFailedOnArray
{
    use SerializesModels;

    public string     $error;
    public array      $journal;
    public RuleAction $ruleAction;

    public function __construct(RuleAction $ruleAction, array $journal, string $error)
    {
        app('log')->debug('Created new RuleActionFailedOnArray');
        $this->ruleAction = $ruleAction;
        $this->journal    = $journal;
        $this->error      = $error;
    }
}
