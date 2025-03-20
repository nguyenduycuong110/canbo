<?php  
namespace App\Services\Interfaces\User;
use App\Services\Interfaces\BaseServiceInterface;
use Illuminate\Http\Request;

interface UserCatalogueServiceInterface extends BaseServiceInterface {
    public function save($request, ?int $id = null);
}