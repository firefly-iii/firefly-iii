<?php


class Component extends Firefly\Database\SingleTableInheritanceEntity
{

    public static $rules
        = [
            'user_id'           => 'exists:users,id|required',
            'name'              => 'required|between:1,255',
            'class'             => 'required',
            'component_type_id' => 'required|exists:component_types,id'
        ];
    protected $table = 'components';
    protected $subclassField = 'class';

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