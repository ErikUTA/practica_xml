<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $table = 'countries';

    protected $fillable = [
        'name'
    ];

    public function personAddresses() {
        return $this->hasMany(PersonAddress::class,'country_id');
    }
    public function addresses() {
        return $this->hasMany(Address::class,'country_id');
    }
}
