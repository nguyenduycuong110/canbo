<?php  
namespace App\Services\Interfaces;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

interface BaseServiceInterface {

    public function all();
    public function paginate(Request $request): LengthAwarePaginator | Collection;
    public function findById(int $id = 0): Model | null;
  
}