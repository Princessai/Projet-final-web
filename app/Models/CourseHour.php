<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Database\Factories\CourseHourFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CourseHour extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected static function newFactory(): Factory
{
    return CourseHourFactory::new();
}


}
