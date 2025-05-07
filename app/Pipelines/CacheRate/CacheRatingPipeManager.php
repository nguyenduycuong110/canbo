<?php 
namespace App\Pipelines\CacheRate;
use Illuminate\Pipeline\Pipeline;
use App\Pipelines\CacheRate\Pipes\SeftEvalutionRating;
use App\Pipelines\CacheRate\Pipes\ManagerConfirmedEvaluation;

class CacheRatingPipeManager {

    public function send(mixed $data){
        try {
            return app(Pipeline::class)
                ->send($data)
                ->through([
                    SeftEvalutionRating::class,
                    ManagerConfirmedEvaluation::class,
                ])
                ->thenReturn();
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}