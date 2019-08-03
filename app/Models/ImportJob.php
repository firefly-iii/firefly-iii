<?php
/**
 * ImportJob.php
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
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class ImportJob.
 *
 * @property array  $transactions
 * @property array  $configuration
 * @property User   $user
 * @property int    $user_id
 * @property string $status
 * @property string $stage
 * @property string $key
 * @property string $provider
 * @property string $file_type
 * @property int    $tag_id
 * @property Tag    $tag
 * @property array  $errors
 * @property array  extended_status
 * @property int    id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Attachment[] $attachments
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ImportJob newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ImportJob newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ImportJob query()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ImportJob whereConfiguration($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ImportJob whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ImportJob whereErrors($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ImportJob whereExtendedStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ImportJob whereFileType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ImportJob whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ImportJob whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ImportJob whereProvider($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ImportJob whereStage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ImportJob whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ImportJob whereTagId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ImportJob whereTransactions($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ImportJob whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ImportJob whereUserId($value)
 * @mixin \Eloquent
 */
class ImportJob extends Model
{

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
        = [
            'user_id'         => 'int',
            'created_at'      => 'datetime',
            'updated_at'      => 'datetime',
            'configuration'   => 'array',
            'extended_status' => 'array',
            'transactions'    => 'array',
            'errors'          => 'array',
        ];
    /** @var array Fields that can be filled */
    protected $fillable = ['key', 'user_id', 'file_type', 'provider', 'status', 'stage', 'configuration', 'extended_status', 'transactions', 'errors'];

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @param $value
     *
     * @return mixed
     *
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): ImportJob
    {
        if (auth()->check()) {
            $key = trim($value);
            /** @var User $user */
            $user = auth()->user();
            /** @var ImportJob $importJob */
            $importJob = $user->importJobs()->where('key', $key)->first();
            if (null !== $importJob) {
                return $importJob;
            }
        }
        throw new NotFoundHttpException;
    }

    /**
     * @codeCoverageIgnore
     * @return MorphMany
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * @codeCoverageIgnore
     * @return BelongsTo
     */
    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
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
