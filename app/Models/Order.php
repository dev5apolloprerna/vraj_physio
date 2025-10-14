<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    public $table = 'patientordermaster';
    protected $primaryKey = 'iOrderId'; // Define the primary key

    protected $fillable = [
        'iOrderId', 'patient_id', 'Date', 'iNetAmount', 'GUID', 'iAmount', 'iDiscount', 'DueAmount', 'bill_prefix','IBillId', 'InvoiceDateTime'
    ];
}
