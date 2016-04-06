<?php
declare(strict_types = 1);
/**
 * ExportJob.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Models;

use Auth;
use Illuminate\Database\Eloquent\Model;
use Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class ExportJob
 *
 * @package FireflyIII
 * @property integer               $id
 * @property \Carbon\Carbon        $created_at
 * @property \Carbon\Carbon        $updated_at
 * @property integer               $user_id
 * @property string                $key
 * @property string                $status
 * @property-read \FireflyIII\User $user
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\ExportJob whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\ExportJob whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\ExportJob whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\ExportJob whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\ExportJob whereKey($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\ExportJob whereStatus($value)
 * @mixin \Eloquent
 */
class ExportJob extends Model
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
     * @param $status
     */
    public function change($status)
    {
        Log::debug('Job ' . $this->key . ' to status "' . $status . '".');
        $this->status = $status;
        $this->save();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('FireflyIII\User');
    }
}
