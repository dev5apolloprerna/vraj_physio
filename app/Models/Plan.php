<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    public $table = 'plan_master';
    protected $primaryKey = 'plan_id'; // Define the primary key

    protected $fillable = [
        'plan_id', 'clinic_id','plan_name', 'no_of_setting', 'setting_threshold_notification', 'treatment_id', 'amount'
    ];
}


