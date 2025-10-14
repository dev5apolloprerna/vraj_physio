<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseNo extends Model
{
    public $table = 'case_master';
    protected $primaryKey = 'case_id'; // Define the primary key
    
    public $timestamps = false; // Disable timestamps

    protected $fillable = [
        'case_prefix', 'case_number', 'case_suffix'
    ];
}


