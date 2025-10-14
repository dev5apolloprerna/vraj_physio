<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettingBillId extends Model
{
    use HasFactory;
    
    public $table = 'setting_bill_id'; // Specify the table name
    protected $primaryKey = 'id'; // Define the primary key
    
    public $timestamps = false; // Disable timestamps
    
    protected $fillable = [
        'id', 'bill_prefix','billId'
    ];
}
