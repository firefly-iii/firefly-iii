<?php

class Importentry extends Eloquent {
    public function importmap()
    {
        return $this->belongsTo('Importmap');
    }
} 