<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\HasQuery;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable 
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasQuery;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account',
        'email',
        'name',
        'cid',
        'user_catalogue_id',
        'hide_date',
        'password',
        'birthday',
        'image',
        'parent_id',
        'lft',
        'rgt',
        'level',
        'team_id',
        'unit_id',
        'province_id',
        'district_id',
        'ward_id',
        'address',
        'publish',
        'phone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $with = [
        'units',
        'teams',
        'user_catalogues',
        // 'managers',
        // 'subordinates',
        // 'statistics'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

   

    public function user_catalogues(): BelongsTo{
        return $this->belongsTo(UserCatalogue::class, 'user_catalogue_id', 'id');
    }

    public function teams(): BelongsTo{
        return $this->belongsTo(Team::class, 'team_id', 'id');
    }

    public function units(): BelongsTo{
        return $this->belongsTo(Unit::class, 'unit_id', 'id');
    }

    public function evaluations(){
        return $this->hasMany(Evaluation::class, 'user_id');
    }

    public function statistics(){
        return $this->hasMany(Statistic::class, 'user_id');
    }

    public function subordinates() // 
    {
        return $this->belongsToMany(User::class, 'user_subordinate', 'manager_id', 'subordinate_id');
    }
    
    // Quan hệ với cấp trên (nhiều đội phó)
    public function managers()
    {
        return $this->belongsToMany(User::class, 'user_subordinate', 'subordinate_id', 'manager_id');
    }

    public function getRelations(): array {
        return ['user_catalogues','teams','units','departments', 'managers'];
    }

}
