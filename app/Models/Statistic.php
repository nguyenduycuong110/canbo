<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasQuery;

class Statistic extends Model
{

    use HasQuery;

    protected $fillable = [
        'working_days_in_month',
        'leave_days_with_permission',
        'leave_days_without_permission',
        'violation_count',
        'violation_behavior',
        'disciplinary_action',
        'user_id',
        'month'
    ];

    public function getRelations(): array {
        return [];
    }

}
