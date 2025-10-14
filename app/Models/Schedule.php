<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;
    
    public $table = 'schedule'; // Specify the table name
    protected $primaryKey = 'scheduleid'; // Define the primary key
    
    public $timestamps = false; // Disable timestamps
    
    protected $fillable = [
        'scheduleid', 'days', 'therapist_id', 'clinic_id', 'start_time', 'end_time', 'treatment_id', 'maximum_patient'
    ];
}
