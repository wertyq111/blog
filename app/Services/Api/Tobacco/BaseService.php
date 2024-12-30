<?php

namespace App\Services\Api\Tobacco;

use App\Models\Tobacco\TobaccoCustomer;
use App\Models\Tobacco\TobaccoSupplyStageNumber;
use App\Services\Api\BaseService as ParentBaseService;
use App\Models\Tobacco\TobaccoStage;
use Illuminate\Support\Facades\DB;

class BaseService extends ParentBaseService
{
    public function __construct()
    {
        $this->stageModel = new TobaccoStage();
        $this->customerModel = new TobaccoCustomer();
        $this->numberModel = new TobaccoSupplyStageNumber();
    }

    /**
     * 获取档位列表
     *
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/12/27 10:53
     */
    public function getStages()
    {
        $stages = [];
        $list = $this->stageModel->get()->toArray();
        foreach ($list as $l) {
            $stages['stage' . $l['id']] = $l['name'];
        }

        return $stages;
    }

    /**
     * 获取档位信息
     *
     * @param $id
     * @return null
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/12/27 14:17
     */
    public function getStage($id)
    {
        $stage = $this->stageModel->find($id);

        return $stage ? $stage->toArray() : null;
    }

    /**
     * 获取客户信息
     *
     * @param $id
     * @return null
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/12/27 14:17
     */
    public function getCustomer($id)
    {
        $customer = $this->customerModel->find($id);

        return $customer ? $customer->toArray() : null;
    }

    /**
     * @param $param
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/12/26 13:58
     */
    public function getSupplyStatistics($param)
    {
        $map = [];

        // 结单日期查询
        $settleDate = getter($param, 'settle_date', null);
        if ($settleDate && is_array($settleDate)) {
            $map[] = ['settle_date', '>=', $settleDate[0]];
            $map[] = ['settle_date', '<=', $settleDate[1]];
        }

        $stages = $this->getStages();

        // 统计字段
        $statisticResults = [];

        foreach (array_keys($stages) as $stage) {
            $stageId = str_replace("stage", "", $stage);
            $recordMap = $map;
            $recordMap[] = ['n.tobacco_stage_id', $stageId];
            $query = $this->numberModel->from('tobacco_supply_stage_numbers as n')
                ->select(DB::raw('IF(SUM(n.number) > 0, SUM(n.number), 0) as number'))
                ->leftJoin('tobacco_supplies as s', 'n.tobacco_supply_id', 's.id')
                ->where($recordMap)->first();
            $statisticResults[$stage] = $query->number;
        }

        return $statisticResults;
    }
}
