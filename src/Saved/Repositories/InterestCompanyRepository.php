<?php

declare(strict_types=1);

namespace Corp104\Jbc\Saved\Repositories;

use Carbon\Carbon;
use Corp104\Jbc\Saved\Models\InterestCompany;

class InterestCompanyRepository
{
    public const FLAG_LIST_ALL = 0;
    public const FLAG_LIST_SUBSCRIBED = 1;
    public const ACTION_UNSUBSCRIBE = 0;
    public const ACTION_SUBSCRIBE = 1;

    /**
     * 取得儲存公司列表 by idNo & 訂閱狀態
     *
     * @param  int $idNo
     * @param  bool $subscriptionFlag 0:全取 1:僅取已訂閱
     *
     * @return array
     */
    public function findByIdNo(int $idNo, int $subscriptionFlag = self::FLAG_LIST_ALL): array
    {
        $model = InterestCompany::where('id_no', $idNo);
        if ($subscriptionFlag === self::FLAG_LIST_SUBSCRIBED) {
            $model->where('notify', 1);
        }

        return $model->orderByDesc('input_date')
            ->pluck('custno')
            ->toArray();
    }

    /**
     * 多筆新增儲存公司
     *
     * @param  int $idNo
     * @param  array $custNos
     *
     * @return int
     */
    public function insertMany(int $idNo, array $custNos): int
    {
        $data = [];
        foreach ($custNos as $custNo) {
            $data[] = [
                'id_no' => $idNo,
                'custno' => $custNo,
                'input_date' => Carbon::now(),
            ];
        }
        InterestCompany::insert($data);

        return count($custNos);
    }

    /**
     * 多筆取消儲存公司
     *
     * @param  int $idNo
     * @param  array $custNos
     *
     * @return int
     */
    public function deleteMany(int $idNo, array $custNos): int
    {
        return InterestCompany::where('id_no', $idNo)
            ->whereIn('custno', $custNos)
            ->delete();
    }

    /**
     * 更新訂閱狀態
     *
     * @param  int $idNo
     * @param  array $custNos
     * @param  int $notify 訂閱新工作通知設定 0:未訂閱 1:訂閱
     *
     * @return int
     */
    public function updateNotify(int $idNo, array $custNos, int $notify): int
    {
        return InterestCompany::where('id_no', $idNo)
            ->whereIn('custno', $custNos)
            ->update([
                'notify' => $notify,
            ]);
    }
}
