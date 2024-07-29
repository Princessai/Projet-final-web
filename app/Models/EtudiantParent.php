<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EtudiantParent extends Model
{
    use HasFactory;

    public function etudiantParent(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parentInfo(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
