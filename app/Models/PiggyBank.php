<?php
declare(strict_types = 1);

namespace FireflyIII\Models;

use Auth;
use Crypt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * FireflyIII\Models\PiggyBank
 *
 * @property integer                                                             $id
 * @property \Carbon\Carbon                                                      $created_at
 * @property \Carbon\Carbon                                                      $updated_at
 * @property \Carbon\Carbon                                                      $deleted_at
 * @property integer                                                             $account_id
 * @property string                                                              $name
 * @property float                                                               $targetamount
 * @property \Carbon\Carbon                                                      $startdate
 * @property \Carbon\Carbon                                                      $targetdate
 * @property integer                                                             $order
 * @property boolean                                                             $encrypted
 * @property boolean                                                             $remind_me
 * @property integer                                                             $reminder_skip
 * @property-read Account                                                        $account
 * @property-read \Illuminate\Database\Eloquent\Collection|PiggyBankRepetition[] $piggyBankRepetitions
 * @property-read \Illuminate\Database\Eloquent\Collection|PiggyBankEvent[]      $piggyBankEvents
 * @property string                                                              $reminder
 * @property PiggyBankRepetition                                                 $currentRep
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\PiggyBank whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\PiggyBank whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\PiggyBank whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\PiggyBank whereAccountId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\PiggyBank whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\PiggyBank whereTargetamount($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\PiggyBank whereStartdate($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\PiggyBank whereTargetdate($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\PiggyBank whereReminder($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\PiggyBank whereReminderSkip($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\PiggyBank whereRemindMe($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\PiggyBank whereOrder($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\PiggyBank whereDeletedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\PiggyBank whereEncrypted($value)
 * @mixin \Eloquent
 */
class PiggyBank extends Model
{
    use SoftDeletes;

    protected $fillable
                      = ['name', 'account_id', 'order', 'targetamount', 'startdate', 'targetdate', 'remind_me', 'reminder_skip'];
    protected $hidden = ['targetamount_encrypted', 'encrypted'];
    protected $dates  = ['created_at', 'updated_at', 'deleted_at', 'startdate', 'targetdate'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account()
    {
        return $this->belongsTo('FireflyIII\Models\Account');
    }

    /**
     * Grabs the PiggyBankRepetition that's currently relevant / active
     *
     * @returns PiggyBankRepetition
     */
    public function currentRelevantRep()
    {
        if (!is_null($this->currentRep)) {
            return $this->currentRep;
        }
        // repeating piggy banks are no longer supported.
        $rep              = $this->piggyBankRepetitions()->first(['piggy_bank_repetitions.*']);
        $this->currentRep = $rep;

        return $rep;


    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function piggyBankRepetitions()
    {
        return $this->hasMany('FireflyIII\Models\PiggyBankRepetition');
    }

    /**
     *
     * @param $value
     *
     * @return string
     */
    public function getNameAttribute($value)
    {

        if (intval($this->encrypted) == 1) {
            return Crypt::decrypt($value);
        }

        return $value;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function piggyBankEvents()
    {
        return $this->hasMany('FireflyIII\Models\PiggyBankEvent');
    }

    /**
     *
     * @param $value
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name']      = Crypt::encrypt($value);
        $this->attributes['encrypted'] = true;
    }

    /**
     * @param $value
     */
    public function setTargetamountAttribute($value)
    {
        $this->attributes['targetamount'] = strval(round($value, 2));
    }

    /**
     * @param PiggyBank $value
     *
     * @return PiggyBank
     */
    public static function routeBinder(PiggyBank $value)
    {
        if (Auth::check()) {
            if ($value->account->user_id == Auth::user()->id) {
                return $value;
            }
        }
        throw new NotFoundHttpException;
    }
}
