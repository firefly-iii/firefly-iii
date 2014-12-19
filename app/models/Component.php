<?php
use Watson\Validating\ValidatingTrait;

/**
 * Class Component
 */
class Component extends Eloquent
{

    public static $rules
                                 = [
            'user_id' => 'exists:users,id|required',
            'name'    => 'required|between:1,100|alphabasic',
            'class'   => 'required',
        ];
    protected     $dates         = ['deleted_at', 'created_at', 'updated_at'];
    protected     $fillable      = ['name', 'user_id','class'];
    protected     $table         = 'components';
    use ValidatingTrait;

//    /**
//     *  remove this method in favour of something in the FireflyIII libraries.
//     *
//     * @return Carbon
//     */
//    public function lastActionDate()
//    {
//        $transaction = $this->transactionjournals()->orderBy('updated_at', 'DESC')->first();
//        if (is_null($transaction)) {
//            return null;
//        }
//
//        return $transaction->date;
//    }
} 