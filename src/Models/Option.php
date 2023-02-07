<?php

namespace Eminiarts\Aura\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
    use HasFactory;

    protected $casts = [
        'value' => 'array',
    ];

    protected $fillable = ['name', 'value'];
}
