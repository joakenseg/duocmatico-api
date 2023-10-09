<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Career extends Model
{
    use HasFactory;

    public $fillable = [
        'name',
    ];

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function calendars(): HasMany
    {
        return $this->hasMany(Calendar::class, 'calendarable_id');
    }
}
