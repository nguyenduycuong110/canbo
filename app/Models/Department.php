<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasQuery;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Department extends Model
{

    use HasQuery;

    protected $fillable = [
        'name',
        'description',
        'publish',
    ];


    public function getRelations(): array {
        return [];
    }

}
