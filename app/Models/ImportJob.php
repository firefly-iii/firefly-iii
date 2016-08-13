<?php
/**
 * ImportJob.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Models;

use Auth;
use Crypt;
use Illuminate\Database\Eloquent\Model;
use Storage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


/**
 * FireflyIII\Models\ImportJob
 *
 * @property integer               $id
 * @property \Carbon\Carbon        $created_at
 * @property \Carbon\Carbon        $updated_at
 * @property integer               $user_id
 * @property string                $key
 * @property string                $file_type
 * @property string                $status
 * @property array                 $configuration
 * @property-read \FireflyIII\User $user
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\ImportJob whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\ImportJob whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\ImportJob whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\ImportJob whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\ImportJob whereKey($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\ImportJob whereFileType($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\ImportJob whereStatus($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\ImportJob whereConfiguration($value)
 * @mixin \Eloquent
 * @property string                $extended_status
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\ImportJob whereExtendedStatus($value)
 */
class ImportJob extends Model
{

    /**
     * @param $value
     *
     * @return mixed
     * @throws NotFoundHttpException
     */
    public static function routeBinder($value)
    {
        if (Auth::check()) {
            $model = self::where('key', $value)->where('user_id', Auth::user()->id)->first();
            if (!is_null($model)) {
                return $model;
            }
        }
        throw new NotFoundHttpException;
    }

    /**
     * @param int $count
     */
    public function addStepsDone(int $count)
    {
        $status = $this->extended_status;
        $status['steps_done'] += $count;
        $this->extended_status = $status;
        $this->save();

    }

    /**
     * @param int $count
     */
    public function addTotalSteps(int $count)
    {
        $status = $this->extended_status;
        $status['total_steps'] += $count;
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
        if (strlen($value) == 0) {
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
        if (strlen($value) == 0) {
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
     * @return string
     */
    public function uploadFileContents(): string
    {
        $fileName         = $this->key . '.upload';
        $disk             = Storage::disk('upload');
        $encryptedContent = $disk->get($fileName);
        $content          = Crypt::decrypt($encryptedContent);

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
