<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasQuery;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class UserCatalogue extends Model
{

    use HasQuery;

    protected $fillable = [
        'name',
        'level',
        'can_create_tasks',
        'publish',
        'description',
    ];

    public function getRelations(): array{
        return ['permissions'];
    }

    public function permissions(): BelongsToMany{
        return $this->belongsToMany(Permission::class, 'user_catalogue_permission', 'user_catalogue_id', 'permission_id');
    }

}
