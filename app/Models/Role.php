<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Role extends Model
{
    use HasFactory;
    public $timestamps = false;
    public function roleUsers(): HasMany // les utilisateurs du role
    {
        return $this->hasMany(User::class);
    }

}
