<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasQuery;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Status extends Model
{

    use HasQuery;

    protected $fillable = [
        'name',
        'description',
        'publish',
        'point',
        'level'
    ];


    public function getRelations(): array {
        return [];
    }

}
