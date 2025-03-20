<?php  
namespace App\Traits;
use Illuminate\Support\Facades\DB;


trait HasTransaction {

    protected function beginTransaction(): self {
        DB::beginTransaction();
        return $this;
    }

    protected function commit(): self {
        DB::commit();
        return $this;
    }

    protected function beforeSave(): self {
        return $this;
    }

    protected function afterSave(): self {
        return $this;
    }
    

}