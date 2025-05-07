<?php 
namespace App\Pipelines\Rate;
use Illuminate\Pipeline\Pipeline;
use App\Pipelines\Rate\Pipes\SeftEvalutionRating;
use App\Pipelines\Rate\Pipes\ManagerConfirmedEvaluation;
use App\Pipelines\Rate\Pipes\SubordinateRating;

class RatingPipeManager {

    public function send(mixed $data){
        try {
            return app(Pipeline::class)
                    ->send($data)
                    ->through([
                        SeftEvalutionRating::class,
                        ManagerConfirmedEvaluation::class,
                        SubordinateRating::class
                    ])
                    ->thenReturn();
        } catch (\Throwable $th) {
            throw $th;
        }
    }

}