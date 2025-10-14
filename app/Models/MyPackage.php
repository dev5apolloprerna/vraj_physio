<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MyPackage extends Model
{
    use HasFactory;
    
    public $table = 'temp_patient_package'; // Specify the table name
    protected $primaryKey = 'tempid'; // Define the primary key
    
    public $timestamps = false; // Disable timestamps
    
    protected $fillable = [
        'tempid', 'patient_id', 'treatment_id', 'plan_id', 'amount', 'clinic_id'
    ];
}
