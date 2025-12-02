<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class CashExpense extends Model
{
    protected $table = 'cash_expenses';

    protected $fillable = [
        'cash_expense',
        'amount',
        'expense_date',
        'clinic_id',
        'branch_id',
        'strDescription',
        'created_by',
        'updated_by',
    ];

    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $casts = [
        'expense_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    
    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }
    
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
