<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetaData extends Model
{
    use HasFactory;
    public $table = 'meta_data';

    protected $fillable = [
        'metaTitle',
        'metaKeyword',
        'metaDescription',
        'head',
        'body',
        'strIP',

    ];

    protected $dates = [
        'created_at',
        'updated_at',

    ];
}