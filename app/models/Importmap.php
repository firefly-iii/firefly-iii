<?php

/**
 * Class Importmap
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