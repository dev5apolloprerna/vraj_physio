<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Treatment extends Model
{
    use HasFactory;
    
    public $table = 'treatment_master'; // Specify the table name
    protected $primaryKey = 'treatment_id'; // Define the primary key
    
    
    protected $fillable = [
        'treatment_id', 'clinic_id', 'treatment_name', 'therpist_Id', 'amount'
    ];
}
