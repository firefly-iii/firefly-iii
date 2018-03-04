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

use Crypt;
use FireflyIII\Exceptions\FireflyException;
use Illuminate\Database\Eloquent\Model;
use Log;
use Storage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class ImportJob.
 */
class ImportJob extends Model
{
    /**
     * @var array
     */
    public $validStatus
        = [
            'new',
            'configuring',
            'configured',
            'running',
            'error',
            'finished',
        ];
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
     * @param $value
     *
     * @return mixed
     *
     * @throws NotFoundHttpException
     * @throws FireflyException
     */
    public static function routeBinder(string $value): ImportJob
    {
        if (auth()->check()) {
            $key       = trim($value);
            $importJob = auth()->user()->importJobs()->where('key', $key)->first();
            if (null !== $importJob) {
                // must have valid status:
                if (!in_array($importJob->status, $importJob->validStatus)) {
                    throw new FireflyException(sprintf('ImportJob with key "%s" has invalid status "%s"', $importJob->key, $importJob->status));
                }

                return $importJob;
            }
        }
        throw new NotFoundHttpException;
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
        Log::debug(sprintf('Add %d to total steps for job "%s" making total steps %d', $count, $this->key, $status['steps']));
    }

    /**
     * @param string $status
     *
     * @throws FireflyException
     */
    public function change(string $status): void
    {
        if (in_array($status, $this->validStatus)) {
            Log::debug(sprintf('Job status set (in model) to "%s"', $status));
            $this->status = $status;
            $this->save();

            return;
        }
        throw new FireflyException(sprintf('Status "%s" is invalid for job "%s".', $status, $this->key));

    }

    /**
     * @param $value
     *
     * @return mixed
     */
    public function getConfigurationAttribute($value)
    {
        if (null === $value) {
            return [];
        }
        if (0 === strlen($value)) {
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
        if (0 === strlen(strval($value))) {
            return [];
        }

        return json_decode($value, true);
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     */
    public function setConfigurationAttribute($value)
    {
        $this->attributes['configuration'] = json_encode($value);
    }

    /**
     * @codeCoverageIgnore
     *
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
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function uploadFileContents(): string
    {
        $fileName         = $this->key . '.upload';
        $disk             = Storage::disk('upload');
        $encryptedContent = $disk->get($fileName);
        $content          = Crypt::decrypt($encryptedContent);
        $content          = trim($content);
        Log::debug(sprintf('Content size is %d bytes.', strlen($content)));

        return $content;
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
