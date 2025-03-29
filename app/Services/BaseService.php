<?php 
namespace App\Services;
use App\Services\Interfaces\BaseServiceInterface;
use App\Traits\HasTransaction;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Lang;
use App\Traits\HasRelation;

abstract class BaseService implements BaseServiceInterface{

    use HasTransaction, HasRelation;

    protected $nestedset;

    private $repository;
    private $model;
    private $result;
    protected $modelData;

    protected $fieldSearchs = ['name'];
    protected $simpleFilter = ['publish','user_id']; // hook
    protected $complexFilter = ['id']; // hook
    protected $dateFilter = ['created_at', 'updated_at'];
    protected $sort = ['id', 'asc'];

    protected $with = []; 


    protected const PERPAGE = 15;


    public function __construct(
        $repository
    )
    {
        $this->repository = $repository;
    }

    protected abstract function prepareModelData(Request $request);
    

    private function buildFilter(Request $request, array $filters = []): array {
        $conditions = [];
        if(count($filters)){
            foreach($filters as $key => $filter){
                if($request->has($filter)){
                    $conditions[$filter] = $request->{$filter};
                }
            }
        }
        return $conditions;
    }

    public function specifications($request): array{
        return [
            'type' => $request->type === 'all', 
            'perpage' => $request->perpage ?? self::PERPAGE,
            'sort' => $request->sort ? explode(',', $request->sort) : $this->sort,
            'keyword' => [
                'q' => $request->keyword,
                'fields' => $this->fieldSearchs
            ],
            'filters' => [
                'simple' => $this->buildFilter($request, $this->simpleFilter),
                'complex' => $this->buildFilter($request, $this->complexFilter),
                'date' => $this->buildFilter($request, $this->dateFilter),
                'relation' => $request->relationFilter ?? [],
            ],
            'with' => $this->with,
        ];  
    }

    public function paginate(Request $request): LengthAwarePaginator | Collection{
        $specifications = $this->specifications($request);
        return $this->repository->paginate($specifications);
    }

    public function save($request, ?int $id = null){
        try {
            return $this->beginTransaction()
                ->prepareModelData($request)
                ->beforeSave($id)
                ->saveModel($id)
                ->handleRelations($request)
                ->afterSave()
                ->commit()
                ->getResult();

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    private function saveModel(?int $id = null): self{
        if($id){
            $this->model = $this->repository->update($id, $this->modelData);
        }else{
            $this->model = $this->repository->create($this->modelData);
        }
        $this->result = $this->model;
        return $this;
    } 

    private function getResult(): mixed{
        return $this->result;
    }

    public function findById(int $id = 0): Model | null{
        try {
            if(!$model = $this->repository->findById($id)){
                throw new ModelNotFoundException(Lang::get('message.not_found'));
            }
            return $model;
        } catch (\Throwable $th) {
           throw $th;
        }
    }

    public function findByCode(int $code = 0, $relation): Model | null{
        $model = $this->repository->findByCode($code, $relation);
        return $model;
    }

    public function destroy(int $id = 0): bool{
        try {
            if(!$model = $this->repository->findById($id)){
                throw new ModelNotFoundException(Lang::get('message.not_found'));
            }   
            return $this->repository->delete($model);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function updateStatus($post = []){
        try {
            $id = $post['modelId'];
            if(!$this->repository->findById($id)){
                throw new ModelNotFoundException(Lang::get('message.not_found'));
            }   
            $payload[$post['field']] = (($post['value'] == config('apps.general.off')) ? config('apps.general.on') : config('apps.general.off'));
            return $this->repository->update($post['modelId'], $payload);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function all(){
        try {
            if(!$this->repository->all()){
                throw new ModelNotFoundException(Lang::get('message.not_found'));
            }   
            return $this->repository->all();
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function nestedset(){
        $this->nestedset->Get('level ASC, order ASC');
        $this->nestedset->Recursive(0, $this->nestedset->Set());
        $this->nestedset->Action();
    }
    

}