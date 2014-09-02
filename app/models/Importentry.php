<?php

/**
 * Importentry
 *
 * @property-read \Importmap $importmap
 * @property integer $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $class
 * @property integer $importmap_id
 * @property integer $old
 * @property integer $new
 * @method static \Illuminate\Database\Query\Builder|\Importentry whereId($value) 
 * @method static \Illuminate\Database\Query\Builder|\Importentry whereCreatedAt($value) 
 * @method static \Illuminate\Database\Query\Builder|\Importentry whereUpdatedAt($value) 
 * @method static \Illuminate\Database\Query\Builder|\Importentry whereClass($value) 
 * @method static \Illuminate\Database\Query\Builder|\Importentry whereImportmapId($value) 
 * @method static \Illuminate\Database\Query\Builder|\Importentry whereOld($value) 
 * @method static \Illuminate\Database\Query\Builder|\Importentry whereNew($value) 
 */
class Importentry extends Eloquent {
    public function importmap()
    {
        return $this->belongsTo('Importmap');
    }
} 