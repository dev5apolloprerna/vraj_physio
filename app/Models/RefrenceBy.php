<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefrenceBy extends Model
{
    use HasFactory;
    
    public $table = 'refrence_by'; // Specify the table name
    protected $primaryKey = 'refrence_id'; // Define the primary key
    
    public $timestamps = false; // Disable timestamps
    
    protected $fillable = [
        'refrence_id', 'refrence_name'
    ];
}
