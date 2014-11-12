<?php
use LaravelBook\Ardent\Ardent as Ardent;

/**
 * Importmap
 *
 * @property integer        $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property integer        $user_id
 * @property string         $file
 * @property integer        $totaljobs
 * @property integer        $jobsdone
 * @property-read \User     $user
 * @method static \Illuminate\Database\Query\Builder|\Importmap whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\Importmap whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Importmap whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Importmap whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\Importmap whereFile($value)
 * @method static \Illuminate\Database\Query\Builder|\Importmap whereTotaljobs($value)
 * @method static \Illuminate\Database\Query\Builder|\Importmap whereJobsdone($value)
 */
class Importmap extends Ardent
{
    public static $rules
        = ['user_id' => 'required|exists:users,id', 'file' => 'required', 'totaljobs' => 'numeric|required|min:0', 'jobsdone' => 'numeric|required|min:0',

        ];

    public function pct()
    {
        if ($this->jobsdone == 0 || $this->totaljobs == 0) {
            return 0;
        } else {
            return round((($this->jobsdone / $this->totaljobs) * 100), 1);
        }
    }

    /**
     * User
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('User');
    }
} 