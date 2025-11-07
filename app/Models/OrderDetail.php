<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    use HasFactory;
    public $table = 'patientorderdetail';
    protected $primaryKey = 'iOrderDetailId'; // Define the primary key

    public $timestamps = false; // Disable timestamps

    protected $fillable = [
        'iOrderDetailId', 'iOrderId', 'iTreatmentId', 'iPlanId', 'iAmount', 'iSession'
    ];
}
