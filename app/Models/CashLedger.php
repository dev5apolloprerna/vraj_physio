<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashLedger extends Model
{
    protected $table = 'cash_ledgers';

    public $timestamps = false; // Since created_at and updated_at are nullable and manually managed

    protected $fillable = [
        'order_id',
        'order_payment_id',
        'cash_expense_id',
        'cash_collection_id',
        'clinic_id',
        'op_amt',
        'cr_amt',
        'dr_amt',
        'cl_amt',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
        'strIP',
    ];
}

?>