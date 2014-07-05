<?php


class Component extends Firefly\Database\SingleTableInheritanceEntity
{

    protected $table = 'components';
    protected $subclassField = 'class';

    public static $rules
        = [
            'user_id'           => 'exists:users,id|required',
            'name'              => 'required|between:1,255',
            'component_type_id' => 'required|exists:component_types,id'
        ];


    public function transactions()
    {
        return $this->belongsToMany('Transaction');
    }
    public function transactionjournals()
    {
        return $this->belongsToMany('TransactionJournal');
    }

    public function user()
    {
        return $this->belongsTo('User');
    }

} 