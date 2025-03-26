<?php  
namespace App\Services\Impl\Task;

use App\Services\BaseService;
use Illuminate\Http\Request;
use App\Services\Interfaces\Task\TaskServiceInterface;
use App\Repositories\Task\TaskRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TaskService extends BaseService implements TaskServiceInterface{

    protected $repository;

    protected $with = ['users'];

    public function __construct(
        TaskRepository $repository
    )
    {
        $this->repository = $repository;
        parent::__construct($repository);
    }

    protected function prepareModelData(Request $request): self
    {
        return $this->initializeBasicData($request);
    }

    private function initializeBasicData(Request $request): self {
        $fillable = $this->repository->getFillable();
        $this->modelData = $request->only($fillable);
        return $this;
    }

    public function checkTask(){
        $user = Auth::user();
        if($user->parent_id == 0){
            return $this->repository->getPublishTask();
        }
        $rootNode = DB::table('users')->where('parent_id', 0)->first();
        $branchNodes = DB::table('users')->where('parent_id', $rootNode->id)->get();
        $branchNode = [];
        if($branchNodes) {
            foreach ($branchNodes as $node) {
                if ($node->lft < $user->lft && $node->rgt > $user->rgt) {
                    $branchNode = $node;
                    break;
                }
            }
        }
        if(!$branchNode) {
            $branchNode = $branchNodes->where('id', $user->id)->first();
            if (!$branchNode) {
                return collect();
            }
        }
        $parentIds = [];
        if($rootNode) {
            $parentIds[] = $rootNode->id;
        }
        $branchs = DB::table('users')
        ->where('lft', '<', $user->lft)
        ->where('rgt', '>', $user->rgt)
        ->where('lft', '>=', $branchNode->lft)
        ->where('rgt', '<=', $branchNode->rgt)
        ->pluck('id')
        ->toArray();
        $parentIds = array_merge($parentIds, $branchs);
        return $this->repository->getTaskByCondition($parentIds);
    }
   

}