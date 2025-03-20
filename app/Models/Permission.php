<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasQuery;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{

    use HasQuery;

    protected $fillable = [
        'name',
        'module',
        'title',
        'value',
        'description',
        'publish',
        'user_id',
    ];


    public function getRelations(): array {
        return [];
    }

}
