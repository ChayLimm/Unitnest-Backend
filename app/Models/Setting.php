<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $fillable = [
        'user_id',
        'general_rules',
        'contract_rules',
        'khr_currency',
        'water_price',
        'electricity_price'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}