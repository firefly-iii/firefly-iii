<?php
/**
 * RuleGroup.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Models;

use Carbon\Carbon;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class RuleGroup.
 *
 * @property bool       $active
 * @property User       $user
 * @property Carbon     $created_at
 * @property Carbon     $updated_at
 * @property string     $title
 * @property string     $text
 * @property int        $id
 * @property int        $order
 * @property Collection $rules
 * @property string     description
 */
class RuleGroup extends Model
{
    use SoftDeletes;
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
        = [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'active'     => 'boolean',
            'order'      => 'int',
        ];

    /** @var array Fields that can be filled */
    protected $fillable = ['user_id', 'order', 'title', 'description', 'active'];

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @param string $value
     *
     * @return RuleGroup
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): RuleGroup
    {
        if (auth()->check()) {
            $ruleGroupId = (int)$value;
            /** @var User $user */
            $user = auth()->user();
            /** @var RuleGroup $ruleGroup */
            $ruleGroup = $user->ruleGroups()->find($ruleGroupId);
            if (null !== $ruleGroup) {
                return $ruleGroup;
            }
        }
        throw new NotFoundHttpException;
    }

    /**
     * @codeCoverageIgnore
     * @return HasMany
     */
    public function rules(): HasMany
    {
        return $this->hasMany(Rule::class);
    }

    /**
     * @codeCoverageIgnore
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
