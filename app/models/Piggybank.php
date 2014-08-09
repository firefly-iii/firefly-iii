<?php
use Carbon\Carbon;
use LaravelBook\Ardent\Ardent as Ardent;

/**
 * Piggybank
 *
 * @property integer        $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property integer        $account_id
 * @property \Carbon\Carbon $targetdate
 * @property string         $name
 * @property float          $amount
 * @property float          $target
 * @property integer        $order
 * @property-read \Account  $account
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereAccountId($value)
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereTargetdate($value)
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereAmount($value)
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereTarget($value)
 * @method static \Illuminate\Database\Query\Builder|\Piggybank whereOrder($value)
 */
class Piggybank extends Ardent
{
    public static $rules
        = [
            'name'       => 'required|between:1,255',
            'account_id' => 'required|exists:accounts,id',
            'targetdate' => 'date',
            'amount'     => 'required|min:0',
            'target'     => 'required|min:1',
            'order'      => 'required:min:1',
        ];

    public static function factory()
    {
        $start = new Carbon;
        $start->endOfMonth();

        return [
            'name'       => 'string',
            'account_id' => 'factory|Account',
            'targetdate' => $start,
            'amount'     => 0,
            'target'     => 100,
            'order'      => 1
        ];
    }

    public function account()
    {
        return $this->belongsTo('Account');
    }

    public function getDates()
    {
        return array('created_at', 'updated_at', 'targetdate');
    }

} 