<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Billing extends Model
{
    use HasFactory;
    
    public $table = 'billingmaster'; // Specify the table name
    protected $primaryKey = 'IBillId'; // Define the primary key
    
    public $timestamps = false; // Disable timestamps
    
    protected $fillable = [
        'IBillId', 'strInvoiceId', 'patient_id', 'InvoiceDateTime', 'Netamount', 'Discount', 'Amount'
    ];
}
