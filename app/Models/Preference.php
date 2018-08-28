<?php
/**
 * Preference.php
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
use Crypt;
use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class Preference.
 *
 * @property mixed  $data
 * @property string $name
 * @property Carbon $updated_at
 * @property Carbon $created_at
 * @property int    $id
 * @property User   user
 */
class Preference extends Model
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

    /** @var array Fields that can be filled */
    protected $fillable = ['user_id', 'data', 'name'];

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @param string $value
     *
     * @return Preference
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): Preference
    {
        if (auth()->check()) {
            $preferenceId = (int)$value;
            /** @var User $user */
            $user = auth()->user();
            /** @var Preference $preference */
            $preference = $user->preferences()->find($preferenceId);
            if (null !== $preference) {
                return $preference;
            }
        }
        throw new NotFoundHttpException;
    }


    /**
     * @param $value
     *
     * @return mixed
     *
     * @throws FireflyException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getDataAttribute($value)
    {
        $result = null;
        try {
            $data = Crypt::decrypt($value);
        } catch (DecryptException $e) {
            Log::error(sprintf('Could not decrypt preference: %s', $e->getMessage()), ['id' => $this->id, 'name' => $this->name, 'data' => $value]);
            throw new FireflyException(
                sprintf('Could not decrypt preference #%d. If this error persists, please run "php artisan cache:clear" on the command line.', $this->id)
            );
        }
        $serialized = true;
        try {
            unserialize($data, ['allowed_classes' => false]);
        } /** @noinspection BadExceptionsProcessingInspection */ catch (Exception $e) {
            $serialized = false;
        }
        if (!$serialized) {
            $result = json_decode($data, true);
        }
        if ($serialized) {
            Log::error(sprintf('Preference #%d ("%s") was stored as serialised object. It will be deleted and recreated.', $this->id, $this->name));
        }

        return $result;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     *
     * @throws \Illuminate\Contracts\Encryption\EncryptException
     */
    public function setDataAttribute($value): void
    {
        $this->attributes['data'] = Crypt::encrypt(json_encode($value));
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
