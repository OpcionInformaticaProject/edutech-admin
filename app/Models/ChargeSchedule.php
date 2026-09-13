<?php

namespace App\Models;

use Database\Factories\ChargeScheduleFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChargeSchedule extends Model
{
    /** @use HasFactory<ChargeScheduleFactory> */
    use HasFactory, HasUlids;

    protected $guarded = [];
}
