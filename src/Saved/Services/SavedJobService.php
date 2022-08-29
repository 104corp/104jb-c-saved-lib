<?php

declare(strict_types=1);

namespace Corp104\Jbc\Saved\Services;

use Corp104\Jbc\Saved\Exceptions\ErrorCode;
use Corp104\Jbc\Saved\Exceptions;
use Corp104\Jbc\Saved\Repositories\NsBuffetRepository;
use Illuminate\Contracts\Cache\Repository as Cache;

class SavedJobService
{
    public const LIST_CACHE_KEY = 'saved_job_list_';
    private const LIST_CACHE_TTL = 180;
    private const TOTAL_LIMIT = 200;

    private NsBuffetRepository $nsBuffetRepository;
    private Cache $cache;

    public function __construct(
        NsBuffetRepository $nsBuffetRepository,
        Cache $cache
    ) {
        $this->nsBuffetRepository = $nsBuffetRepository;
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

        $savedJobNos = $this->nsBuffetRepository->findByIdNo($idNo);
        if (!empty($savedJobNos)) {
            $this->cache->put($cacheKey, $savedJobNos, self::LIST_CACHE_TTL);
        }

        return $savedJobNos;
    }

    /**
     * 多筆刪除儲存工作
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
        $savedJobNos = $this->list($idNo);

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

        $recordCount = $this->nsBuffetRepository->createMany($idNo, $validJobs);
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
        $savedJobNos = $this->list($idNo);
        $validJobNos = array_intersect($savedJobNos, $jobNos);
        if (count($validJobNos) === 0) {
            return 0;
        }

        $recordCount = $this->nsBuffetRepository->deleteMany($idNo, $validJobNos);
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
}