<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Designation extends Model
{
    use HasFactory;
    
    public $table = 'designation'; // Specify the table name
    protected $primaryKey = 'designation_id'; // Define the primary key
    
    public $timestamps = false; // Disable timestamps
    
    protected $fillable = [
        'designation_id',
        'designation_name'
    ];
}
