<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasQuery;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Evaluation extends Model
{

    use HasQuery;

    protected $fillable = [
        'task_id',
        'user_id',
        'start_date',
        'due_date',
        'completion_date',
        'output',
        'note',
        'file',
        'total_tasks',
        'overachieved_tasks',
        'completed_tasks_ontime',
        'failed_tasks_count',
    ];

    protected $with = [
       'statuses'  
    ];


    public function getRelations(): array {
        return ['tasks','statuses', 'users'];
    }

    public function tasks(): BelongsTo{
        return $this->belongsTo(Task::class,'task_id', 'id');
    }
    
    public function statuses(): BelongsToMany{
        return $this->belongsToMany(Status::class, 'evaluation_status', 'evaluation_id', 'status_id')
            ->withPivot([
                'user_id',
                'lock',
                'point',
                'delegate_id'
            ])->withTimestamps();
    }

    public function users(): BelongsTo{
        return $this->belongsTo(User::class,'user_id', 'id');
    }

}
