<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduleTreatment extends Model
{
    use HasFactory;
    
    public $table = 'schedule_treatment'; // Specify the table name
    protected $primaryKey = 'sid'; // Define the primary key
    
    public $timestamps = false; // Disable timestamps
    
    protected $fillable = [
        'sid', 'schedule_id', 'therapist_id','treatment_id'
    ];
}
