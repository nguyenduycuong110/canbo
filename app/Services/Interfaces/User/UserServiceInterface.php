<?php   
namespace App\Services\Interfaces\User;
use App\Services\Interfaces\BaseServiceInterface;

interface UserServiceInterface extends BaseServiceInterface {
    public function getUsersOnBranch($user, $userCatalogueId);
    public function findByIds($ids, $relations = []);
    public function getUserInNode($currentUser);
    public function getUserByLevel($level);
    public function getUserInNodeSortByLevel($currentUser);
    public function getManager($auth, $level);

}