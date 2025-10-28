<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Rules extends Model
{
    use HasFactory;

    protected $fillable = [
        'set_rule_id',
        'premises',
        'conclusion',
        'original_text',
    ];
}
