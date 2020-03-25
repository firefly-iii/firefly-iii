<?php
/**
 * ImportJob.php
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
use FireflyIII\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class ImportJob.
 *
 * @codeCoverageIgnore
 * @deprecated
 * @property array                                                      $transactions
 * @property array                                                      $configuration
 * @property User                                                       $user
 * @property int                                                        $user_id
 * @property string                                                     $status
 * @property string                                                     $stage
 * @property string                                                     $key
 * @property string                                                     $provider
 * @property string                                                     $file_type
 * @property int                                                        $tag_id
 * @property Tag                                                        $tag
 * @property array                                                      $errors
 * @property array                                                      extended_status
 * @property int                                                        id
 * @property Carbon                                                     $created_at
 * @property Carbon                                                     $updated_at
 * @property-read Collection|Attachment[] $attachments
 * @method static Builder|ImportJob newModelQuery()
 * @method static Builder|ImportJob newQuery()
 * @method static Builder|ImportJob query()
 * @method static Builder|ImportJob whereConfiguration($value)
 * @method static Builder|ImportJob whereCreatedAt($value)
 * @method static Builder|ImportJob whereErrors($value)
 * @method static Builder|ImportJob whereExtendedStatus($value)
 * @method static Builder|ImportJob whereFileType($value)
 * @method static Builder|ImportJob whereId($value)
 * @method static Builder|ImportJob whereKey($value)
 * @method static Builder|ImportJob whereProvider($value)
 * @method static Builder|ImportJob whereStage($value)
 * @method static Builder|ImportJob whereStatus($value)
 * @method static Builder|ImportJob whereTagId($value)
 * @method static Builder|ImportJob whereTransactions($value)
 * @method static Builder|ImportJob whereUpdatedAt($value)
 * @method static Builder|ImportJob whereUserId($value)
 * @mixin Eloquent
 * @property-read int|null                                              $attachments_count
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
     * @throws NotFoundHttpException
     * @return mixed
     *
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
