<?php

declare(strict_types=1);

namespace Corp104\Jbc\Saved\Repositories;

use Carbon\Carbon;
use Corp104\Jbc\Saved\Models\NsBuffet;

class NsBuffetRepository
{
    /**
     * @param  int $idNo
     *
     * @return array
     */
    public function findByIdNo(int $idNo): array
    {
        return NsBuffet::where('id_no', $idNo)
            ->orderByDesc('input_date')
            ->pluck('jobno')
            ->toArray();
    }

    /**
     * @param  int $idNo
     * @param  array $jobs
     *   [
     *     [
     *        'jobNo' => 123,
     *        'custNo' => 456,
     *     ],
     *   ]
     *
     * @return int
     */
    public function insertMany(int $idNo, array $jobs): int
    {
        $data = [];
        foreach ($jobs as $job) {
            $data[] = [
                'id_no' => $idNo,
                'jobno' => $job['jobNo'],
                'custno' => $job['custNo'],
                'input_date' => Carbon::now(),
            ];
        }
        NsBuffet::insert($data);

        return count($jobs);
    }

    /**
     * @param  int $pid
     * @param  array $jobNos
     *
     * @return int
     */
    public function deleteMany(int $idNo, array $jobNos): int
    {
        return NsBuffet::where('id_no', $idNo)
            ->whereIn('jobno', $jobNos)
            ->delete();
    }
}
