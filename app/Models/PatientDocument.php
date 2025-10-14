<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientDocument extends Model
{
    use HasFactory;
    
    public $table = 'patient_documents'; // Specify the table name
    protected $primaryKey = 'document_id'; // Define the primary key
    
    public $timestamps = false; // Disable timestamps
    
    protected $fillable = [
        'document_id', 'patient_id', 'document'
    ];
}
