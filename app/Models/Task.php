<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasQuery;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{

    use HasQuery;

    protected $fillable = [
        'name',
        'description',
        'publish',
        'user_id'
    ];


    public function getRelations(): array {
        return ['users'];
    }
    
    public function users(): BelongsTo{
        return $this->belongsTo(User::class, 'user_id', 'id');
    }


}
