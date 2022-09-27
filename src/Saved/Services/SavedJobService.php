<?php

declare(strict_types=1);

namespace Corp104\Jbc\Saved\Services;

use Corp104\Jbc\Saved\Exceptions\ErrorCode;
use Corp104\Jbc\Saved\Exceptions;
use Corp104\Jbc\Saved\Repositories\NsBuffetRepository;
use Corp104\Jbc\Search\Job\JobSearch;
use Illuminate\Contracts\Cache\Repository as Cache;

class SavedJobService
{
    public const LIST_CACHE_KEY = 'saved_job_list_0927_';
    private const LIST_CACHE_TTL = 180;
    private const TOTAL_LIMIT = 200;

    private NsBuffetRepository $nsBuffetRepository;
    private JobSearch $jobSearch;
    private Cache $cache;

    public function __construct(
        NsBuffetRepository $nsBuffetRepository,
        JobSearch $jobSearch,
        Cache $cache
    ) {
        $this->nsBuffetRepository = $nsBuffetRepository;
        $this->jobSearch = $jobSearch;
        $this->cache = $cache;
    }

    /**
     * 取得儲存工作列表（不篩選不分頁）
     *
     * @param  int $idNo
     *
     * @return array
     */
    public function list(int $idNo): array
    {
        $cacheKey = $this->getListCacheKey($idNo);
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $savedJobs = $this->nsBuffetRepository->findByIdNo($idNo);
        $savedJobNos = array_column($savedJobs, 'jobno');
        $validJobNos = $this->validJobFilter($savedJobNos);
        $savedList = [];
        foreach ($savedJobs as $savedJob) {
            if (in_array($savedJob['jobno'], $validJobNos)) {
                $savedList[] = [
                    'jobNo' => $savedJob['jobno'],
                    'inputDate' => $savedJob['input_date'],
                ];
            }
        }
        if (!empty($savedList)) {
            $this->cache->put($cacheKey, $savedList, self::LIST_CACHE_TTL);
        }

        return $savedList;
    }

    /**
     * 多筆新增儲存工作
     *
     * @param  int $idNo
     * @param  array $jobs
     * [
     *   [
     *     'jobNo' => 123,
     *     'custNo' => 456,
     *   ]
     * ]
     *
     * @return int
     */
    public function batchCreate(int $idNo, array $jobs): int
    {
        $savedJobNos = array_column($this->list($idNo), 'jobNo');

        $total = count($savedJobNos) + count($jobs);
        if ($total > self::TOTAL_LIMIT) {
            throw new Exceptions\ExceedLimitException(
                ErrorCode::MSG_SAVE_JOB_EXCEED_LIMIT_ERROR,
                ErrorCode::CODE_SAVE_JOB_EXCEED_LIMIT_ERROR
            );
        }

        $validJobs = array_filter($jobs, function ($job) use ($savedJobNos) {
            return !in_array($job['jobNo'], $savedJobNos);
        });
        if (empty($validJobs)) {
            return 0;
        }

        $recordCount = $this->nsBuffetRepository->insertMany($idNo, $validJobs);
        $this->cache->forget($this->getListCacheKey($idNo));

        return $recordCount;
    }

    /**
     * 多筆取消儲存工作
     *
     * @param  int $idNo
     * @param  array $jobNos
     *
     * @return int
     */
    public function batchDelete(int $idNo, array $jobNos): int
    {
        $recordCount = $this->nsBuffetRepository->deleteMany($idNo, $jobNos);
        if ($recordCount > 0) {
            $this->cache->forget($this->getListCacheKey($idNo));
        }

        return $recordCount;
    }

    /**
     * @param  int $idNo
     *
     * @return string
     */
    private function getListCacheKey(int $idNo): string
    {
        return self::LIST_CACHE_KEY . $idNo;
    }

    /**
     * 過濾有效職缺（jobOn + jobOff）, 已刪除職缺會被過濾掉
     *
     * @param array $jobNos
     *
     * @return array
     */
    private function validJobFilter(array $jobNos): array
    {
        $onJobs = $this->jobSearch->onJobs($jobNos, ['RETURNID'])->get();
        $onJobNos = array_column($onJobs, 'RETURNID');
        $offJobs = $this->jobSearch->offJobs($jobNos, ['returnid'])->get();
        $offJobNos = array_column($offJobs, 'returnid');

        return $onJobNos + $offJobNos;
    }
}
