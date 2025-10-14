<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SessionMaster extends Model
{
    use HasFactory;
    
    public $table = 'sessionmaster'; // Specify the table name
    protected $primaryKey = 'iSessionTakenId'; // Define the primary key
    
    public $timestamps = false; // Disable timestamps
    
    protected $fillable = [
        'iSessionTakenId', 'patient_id', 'SessionStartTime','SessionEndTime','treatment_id','therapist_id'
    ];
}
