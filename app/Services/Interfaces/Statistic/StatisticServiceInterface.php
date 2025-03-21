<?php   
namespace App\Services\Interfaces\Statistic;
use App\Services\Interfaces\BaseServiceInterface;

interface StatisticServiceInterface extends BaseServiceInterface {
    public function createOrUpdate($formData);

}