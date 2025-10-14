<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsentMaster extends Model
{
    public $table = 'consent_master';
    protected $primaryKey = 'id'; // Define the primary key
    

    protected $fillable = [
        'id', 'title','clinic_id', 'description'
    ];
}


