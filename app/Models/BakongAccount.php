<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BakongAccount extends Model
{
    use CrudTrait;
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'landlord_id',
        'bakong_id',
        'bakong_name',
        'bakong_location',
    ];

    // Relationships
    public function landlord()
    {
        return $this->belongsTo(User::class, 'landlord_id');
    }
}