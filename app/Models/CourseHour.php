<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Database\Factories\CourseHourFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CourseHour extends Model
{
    use HasFactory;

    protected $fillable = [
        'classe_module_id',
        'type_seance_id',
        'nbre_heure_effectue',
    ];


    public $timestamps = false;

    protected static function newFactory(): Factory
    {
        return CourseHourFactory::new();
    }
}
