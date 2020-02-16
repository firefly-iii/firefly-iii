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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * FireflyIII\Models\LinkType
 *
 * @property int    $journalCount
 * @property string $inward
 * @property string $outward
 * @property string $name
 * @property bool   $editable
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property int    $id
 * Class LinkType
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\TransactionJournalLink[] $transactionJournalLinks
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\LinkType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\LinkType newQuery()
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\LinkType onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\LinkType query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\LinkType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\LinkType whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\LinkType whereEditable($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\LinkType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\LinkType whereInward($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\LinkType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\LinkType whereOutward($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\LinkType whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\LinkType withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\LinkType withoutTrashed()
 * @mixin \Eloquent
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
     * @return LinkType
     *
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): LinkType
    {
        if (auth()->check()) {
            $linkTypeId = (int)$value;
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
