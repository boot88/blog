<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alarm extends Model
{
    protected $fillable = [
        'title','note','date','time','enabled','timezone','last_triggered_at'
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'date' => 'date:Y-m-d',
        'last_triggered_at' => 'datetime',
    ];

    public function isDaily(): bool
    {
        return $this->date === null;
    }
}
