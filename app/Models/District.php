<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasQuery;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends Model
{

    use HasQuery;

    protected $fillable = [
        'name'
    ];


    public function getRelations(): array {
        return [];
    }

    public function provinces(): BelongsTo{
        return $this->belongsTo(Province::class, 'province_code', 'code');
    }

    public function wards(): HasMany{
        return $this->hasMany(Ward::class, 'district_code', 'code');
    }
 

}
