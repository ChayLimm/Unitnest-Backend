<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;


class Telegrambot extends Model
{
    use HasFactory;
    use CrudTrait;

    
    protected $table = 'telegram_bots';

    protected $fillable = [
        'user_id',
        'bot_id',
        'image_url',
        'about',
        'description',
        'username',
        'token',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
