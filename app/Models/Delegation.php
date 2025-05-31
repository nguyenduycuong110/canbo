<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasQuery;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delegation extends Model
{

    use HasQuery;

    protected $fillable = [
        'id',
        'delegator_id',
        'delegate_id',
        'start_date',
        'end_date',
        'is_active',
        'publish',
    ];


    public function delegators(): BelongsTo{
        return $this->belongsTo(User::class, 'delegator_id', 'id');
    }
    
    public function delegates(): BelongsTo{
        return $this->belongsTo(User::class, 'delegate_id', 'id');
    }


}
