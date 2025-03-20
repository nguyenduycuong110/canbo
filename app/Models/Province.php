<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasQuery;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Province extends Model
{

    use HasQuery;

    protected $fillable = [
        'code',
        'name'
    ];


    public function getRelations(): array {
        return ['districts'];
    }

    public function districts(): HasMany{
        return $this->hasMany(District::class, 'province_code', 'code');
    }


}
