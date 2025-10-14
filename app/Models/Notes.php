<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notes extends Model
{
    public $table = 'notes_master';
    protected $primaryKey = 'note_id'; // Define the primary key
    
    public $timestamps = false; // Disable timestamps

    protected $fillable = [
        'note_id', 'title', 'description', 'patient_Id', 'clinic_id'
    ];
}


