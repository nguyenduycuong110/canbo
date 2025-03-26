<?php   
namespace App\Services\Interfaces\Team;
use Illuminate\Http\Request;
use App\Services\Interfaces\BaseServiceInterface;

interface TeamServiceInterface extends BaseServiceInterface {
    public function teamPublish();

}