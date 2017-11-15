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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Models;

use Crypt;
use Illuminate\Database\Eloquent\Model;
use Log;
use Storage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class ImportJob
 *
 * @package FireflyIII\Models
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
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];

    protected $validStatus
        = [
            'new',
            'initialized',
            'configured',
            'running',
            'finished',
        ];

    /**
     * @param $value
     *
     * @return mixed
     * @throws NotFoundHttpException
     */
    public static function routeBinder($value)
    {
        if (auth()->check()) {
            $model = self::where('key', $value)->where('user_id', auth()->user()->id)->first();
            if (!is_null($model)) {
                return $model;
            }
        }
        throw new NotFoundHttpException;
    }

    /**
     * @param int    $index
     * @param string $message
     *
     * @return bool
     */
    public function addError(int $index, string $message): bool
    {
        $extended                     = $this->extended_status;
        $extended['errors'][$index][] = $message;
        $this->extended_status        = $extended;

        return true;
    }

    /**
     * @param int $count
     */
    public function addStepsDone(int $count)
    {
        $status                = $this->extended_status;
        $status['done']        += $count;
        $this->extended_status = $status;
        $this->save();
    }

    /**
     * @param int $count
     */
    public function addTotalSteps(int $count)
    {
        $status                = $this->extended_status;
        $status['steps']       += $count;
        $this->extended_status = $status;
        $this->save();
    }

    /**
     * @param $status
     */
    public function change($status)
    {
        $this->status = $status;
        $this->save();
    }

    /**
     * @param $value
     *
     * @return mixed
     */
    public function getConfigurationAttribute($value)
    {
        if (is_null($value)) {
            return [];
        }
        if (strlen($value) === 0) {
            return [];
        }

        return json_decode($value, true);
    }

    /**
     * @param $value
     *
     * @return mixed
     */
    public function getExtendedStatusAttribute($value)
    {
        if (strlen($value) === 0) {
            return [];
        }

        return json_decode($value, true);
    }

    /**
     * @param $value
     */
    public function setConfigurationAttribute($value)
    {
        $this->attributes['configuration'] = json_encode($value);
    }

    /**
     * @param $value
     */
    public function setExtendedStatusAttribute($value)
    {
        $this->attributes['extended_status'] = json_encode($value);
    }

    /**
     * @param $value
     */
    public function setStatusAttribute(string $value)
    {
        if (in_array($value, $this->validStatus)) {
            $this->attributes['status'] = $value;
        }
    }

    /**
     * @return string
     */
    public function uploadFileContents(): string
    {
        $fileName         = $this->key . '.upload';
        $disk             = Storage::disk('upload');
        $encryptedContent = $disk->get($fileName);
        $content          = Crypt::decrypt($encryptedContent);
        Log::debug(sprintf('Content size is %d bytes.', strlen($content)));

        return $content;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('FireflyIII\User');
    }
}
