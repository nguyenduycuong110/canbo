<?php  
namespace App\Repositories\User;

use App\Repositories\BaseRepository;
use App\Models\UserCatalogue;

class UserCatalogueRepository extends BaseRepository {

    protected $model;

    private const CANBO_LEVEL = 5;

    public function __construct(
        UserCatalogue $model
    )
    {
        $this->model = $model;
        parent::__construct($model);
    }

    public function listSubordinate($currentUserCatalogue){
        return $this->model
            ->where('level','>', $currentUserCatalogue->level)
            ->where('level','<', self::CANBO_LEVEL)
            ->get();
    }

}