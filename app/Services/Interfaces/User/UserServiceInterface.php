<?php   
namespace App\Services\Interfaces\User;
use App\Services\Interfaces\BaseServiceInterface;

interface UserServiceInterface extends BaseServiceInterface {
    public function getUsersOnBranch($user, $userCatalogueId);

}