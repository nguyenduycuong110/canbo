<?php  
namespace App\Traits;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Lang;
use App\Exceptions\RecordNotMatchException;

trait HasRelation {
    
    protected function handleRelations(Request $request): self {
        $relations = $this->repository->getRelations();
        if(count($relations)){
            foreach($relations as $key => $relation){
                if($request->has($relation)){
                    $this->model->{$relation}()->sync($request->{$relation});
                }
            }
        }
        return $this;
    }

}