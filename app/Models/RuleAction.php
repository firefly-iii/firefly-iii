<?php

/**
 * RuleAction.php
 * Copyright (c) 2019 james@firefly-iii.org
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

namespace FireflyIII\Models;

use Carbon\Carbon;
use Eloquent;
use FireflyIII\Support\Models\ReturnsIntegerIdTrait;
use FireflyIII\TransactionRules\Expressions\ActionExpression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
use Symfony\Component\ExpressionLanguage\SyntaxError;


class RuleAction extends Model
{
    use ReturnsIntegerIdTrait;

    protected $casts
                        = [
            'created_at'      => 'datetime',
            'updated_at'      => 'datetime',
            'active'          => 'boolean',
            'order'           => 'int',
            'stop_processing' => 'boolean',
        ];

    protected $fillable = ['rule_id', 'action_type', 'action_value', 'order', 'active', 'stop_processing'];

    public function getValue(array $journal): string
    {
        if (false === config('firefly.feature_flags.expression_engine')) {
            Log::debug('Expression engine is disabled, returning action value as string.');

            return (string)$this->action_value;
        }
        if (true === config('firefly.feature_flags.expression_engine') && str_starts_with($this->action_value, '\=')) {
            // return literal string.
            return substr($this->action_value, 1);
        }
        $expr = new ActionExpression($this->action_value);

        try {
            $result = $expr->evaluate($journal);
        } catch (SyntaxError $e) {
            Log::error(sprintf('Expression engine failed to evaluate expression "%s" with error "%s".', $this->action_value, $e->getMessage()));
            $result = (string)$this->action_value;
        }
        Log::debug(sprintf('Expression engine is enabled, result of expression "%s" is "%s".', $this->action_value, $result));

        return $result;
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(Rule::class);
    }

    protected function order(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int)$value,
        );
    }

    protected function ruleId(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => (int)$value,
        );
    }
}
