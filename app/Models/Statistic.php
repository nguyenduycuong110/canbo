<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasQuery;

class Statistic extends Model
{

    use HasQuery;

    protected $fillable = [
        'publish',
    ];


    public function getRelations(): array {
        return [];
    }

}
