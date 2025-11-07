<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientSchedule extends Model
{
    use HasFactory;
    public $table = 'patient_schedule';
    protected $primaryKey = 'patient_schedule_id'; // Define the primary key

    public $timestamps = false; // Disable timestamps

    protected $fillable = [
        'patient_schedule_id', 'patient_id', 'orderId','day', 'scheduleid', 'schedule_start_time', 'schedule_end_time', 'treatment_id', 'therapist_id'
    ];
}
