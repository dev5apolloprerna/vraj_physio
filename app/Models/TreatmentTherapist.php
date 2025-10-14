<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TreatmentTherapist extends Model
{
    use HasFactory;
    
    public $table = 'treatment_therapist'; // Specify the table name
    protected $primaryKey = 'id'; // Define the primary key
    
    public $timestamps = false; // Disable timestamps

    protected $fillable = [
        'treatment_id', 'therapist_id'
    ];
}
