<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderPayment extends Model
{
    use HasFactory;
    
    public $table = 'orderpayment'; // Specify the table name
    protected $primaryKey = 'OrderPaymentId'; // Define the primary key
    
    public $timestamps = false; // Disable timestamps
    
    protected $fillable = [
        'OrderPaymentId', 'iOrderId','orderDetailId', 'Amount', 'payment_mode', 'bad_dept', 'PaymentDateTime'
    ];
}
