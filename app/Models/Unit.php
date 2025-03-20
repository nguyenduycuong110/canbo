<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasQuery;

class Unit extends Model
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
