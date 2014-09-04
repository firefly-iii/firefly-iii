<?php
use Illuminate\Database\Eloquent\Model as Eloquent;
/**
 * Class Importmap
 *
 * @property-read \User $user
 * @property integer $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property integer $user_id
 * @property string $file
 * @method static \Illuminate\Database\Query\Builder|\Importmap whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\Importmap whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Importmap whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Importmap whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\Importmap whereFile($value)
 */
class Importmap extends Eloquent
{
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