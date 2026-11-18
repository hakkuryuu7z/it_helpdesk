<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketNumberSequence extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'year';
    public $incrementing = false;

    protected $fillable = [
        'year',
        'current_value',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'current_value' => 'integer',
            'updated_at' => 'datetime',
        ];
    }
}
