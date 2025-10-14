<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;
    public $table = 'patient_master';
    protected $primaryKey = 'patient_id'; // Define the primary key

    protected $fillable = [
       'patient_id', 'patient_case_no', 'patient_first_name', 'patient_last_name', 'patient_age', 'dob', 'phone', 'email', 'gender', 'address', 'ref_by'
    ];
}
