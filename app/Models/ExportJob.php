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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class ExportJob.
 *
 * @property User   $user
 * @property string $key
 */
class ExportJob extends Model
{
    /** @var array */
    protected $casts
        = [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];

    /**
     * @param string $value
     *
     * @return ExportJob
     *
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): ExportJob
    {
        if (auth()->check()) {
            $key       = trim($value);
            $exportJob = auth()->user()->exportJobs()->where('key', $key)->first();
            if (null !== $exportJob) {
                return $exportJob;
            }
        }
        throw new NotFoundHttpException;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $status
     */
    public function change($status)
    {
        $this->status = $status;
        $this->save();
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('FireflyIII\User');
    }
}
