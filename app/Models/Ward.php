<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasQuery;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ward extends Model
{

    use HasQuery;

    protected $fillable = [
        'name'
    ];

    public function districts(): BelongsTo{
        return $this->BelongsTo(District::class, 'district_code', 'code');
    }


}
