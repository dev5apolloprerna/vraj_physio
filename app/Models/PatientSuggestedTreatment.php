<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientSuggestedTreatment extends Model
{
    use HasFactory;
    
    public $table = 'patient_suggested_treatment'; // Specify the table name
    protected $primaryKey = 'PatientSTreatmentId'; // Define the primary key
    
    public $timestamps = false; // Disable timestamps
    
    protected $fillable = [
        'PatientSTreatmentId', 'iOrderId','iOrderDetailId','patient_id', 'treatment_id', 'iSessionBuy', 'iUsedSession', 'iAvailableSession','isActive','manually_consumed'
    ];
}
