<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientIn extends Model
{
    use HasFactory;
    public $table = 'patientin';
    protected $primaryKey = 'patientin'; // Define the primary key

    public $timestamps = false; // Disable timestamps

    protected $fillable = [
        'iPatientInId', 'patient_id', 'inDateTime', 'therapist_id', 'treatment_id','isGroupSession','leave','status','patient_schedule_id'
    ];
}
