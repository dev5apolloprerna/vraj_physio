<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientTreatmentLedger extends Model
{
    use HasFactory;
    
    public $table = 'patienttreatementledger'; // Specify the table name
    protected $primaryKey = 'iLedgerId'; // Define the primary key
    
    public $timestamps = false; // Disable timestamps
    
    protected $fillable = [
        'iLedgerId', 'patient_id', 'treatment_id', 'therapist_id', 'iOrderDetailId', 'iSessionTakenId', 'opening_balance', 'credit_balance', 'debit_balance', 'closing_balance'
    ];
}
