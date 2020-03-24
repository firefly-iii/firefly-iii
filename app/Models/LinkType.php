<?php
/**
 * LinkType.php
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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * FireflyIII\Models\LinkType
 *
 * @property int                                                                    $journalCount
 * @property string                                                                 $inward
 * @property string                                                                 $outward
 * @property string                                                                 $name
 * @property bool                                                                   $editable
 * @property Carbon                                                                 $created_at
 * @property Carbon                                                                 $updated_at
 * @property int                                                                    $id
 * Class LinkType
 * @property \Illuminate\Support\Carbon|null                                        $deleted_at
 * @property-read Collection|TransactionJournalLink[] $transactionJournalLinks
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|LinkType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LinkType newQuery()
 * @method static Builder|LinkType onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|LinkType query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|LinkType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkType whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkType whereEditable($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkType whereInward($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkType whereOutward($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkType whereUpdatedAt($value)
 * @method static Builder|LinkType withTrashed()
 * @method static Builder|LinkType withoutTrashed()
 * @mixin Eloquent
 */
class LinkType extends Model
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
            'editable'   => 'boolean',
        ];

    /** @var array Fields that can be filled */
    protected $fillable = ['name', 'inward', 'outward', 'editable'];

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @param $value
     *
     * @throws NotFoundHttpException
     * @return LinkType
     *
     */
    public static function routeBinder(string $value): LinkType
    {
        if (auth()->check()) {
            $linkTypeId = (int) $value;
            $linkType   = self::find($linkTypeId);
            if (null !== $linkType) {
                return $linkType;
            }
        }
        throw new NotFoundHttpException;
    }

    /**
     * @codeCoverageIgnore
     * @return HasMany
     */
    public function transactionJournalLinks(): HasMany
    {
        return $this->hasMany(TransactionJournalLink::class);
    }
}
