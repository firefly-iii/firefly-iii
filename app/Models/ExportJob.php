<?php
/**
 * ExportJob.php
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

use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class ExportJob.
 *
 * @property User   $user
 * @property string $key
 * @property int    $user_id
 * @property string status
 * @property int    id
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $status
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ExportJob newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ExportJob newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ExportJob query()
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ExportJob whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ExportJob whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ExportJob whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ExportJob whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ExportJob whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\FireflyIII\Models\ExportJob whereUserId($value)
 * @mixin \Eloquent
 */
class ExportJob extends Model
{
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
        = [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @param string $value
     *
     * @return ExportJob
     *
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): ExportJob
    {
        if (auth()->check()) {
            $key = trim($value);
            /** @var User $user */
            $user = auth()->user();
            /** @var ExportJob $exportJob */
            $exportJob = $user->exportJobs()->where('key', $key)->first();
            if (null !== $exportJob) {
                return $exportJob;
            }
        }
        throw new NotFoundHttpException;
    }

    /**
     * Change the status of this export job.
     *
     * @param $status
     *
     * @deprecated
     * @codeCoverageIgnore
     */
    public function change($status): void
    {
        $this->status = $status;
        $this->save();
    }

    /**
     * Returns the user this objects belongs to.
     *
     *
     * @return BelongsTo
     * @codeCoverageIgnore
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
