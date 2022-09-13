<?php

declare(strict_types=1);

namespace Corp104\Jbc\Saved\Services;

use Corp104\Jbc\Saved\Exceptions;
use Corp104\Jbc\Saved\Exceptions\ErrorCode;
use Corp104\Jbc\Saved\Repositories\InterestCompanyRepository;
use Illuminate\Contracts\Cache\Repository as Cache;

class SavedCompanyService
{
    public const LIST_CACHE_KEY = 'saved_company_list_';
    public const SUBSCRIBED_LIST_CACHE_KEY = 'subscribed_company_list_';
    private const LIST_CACHE_TTL = 180;
    private const TOTAL_LIMIT = 200;

    private InterestCompanyRepository $interestCompanyRepository;
    private Cache $cache;

    public function __construct(
        InterestCompanyRepository $interestCompanyRepository,
        Cache $cache
    ) {
        $this->interestCompanyRepository = $interestCompanyRepository;
        $this->cache = $cache;
    }

    /**
     * 取得 儲存/訂閱 公司列表（不篩選不分頁）
     *
     * @param  int $idNo
     * @param  int $subscriptionFlag 0:儲存公司全取 1:僅取已訂閱
     *
     * @return array
     */
    public function list(int $idNo, int $subscriptionFlag = InterestCompanyRepository::FLAG_LIST_ALL): array
    {
        $cacheKey = $this->getListCacheKey($idNo, $subscriptionFlag);
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $savedCustNos = $this->interestCompanyRepository->findByIdNo($idNo, $subscriptionFlag);
        if (!empty($savedCustNos)) {
            $this->cache->put($cacheKey, $savedCustNos, self::LIST_CACHE_TTL);
        }

        return $savedCustNos;
    }

    /**
     * 多筆新增儲存公司
     *
     * @param  int $idNo
     * @param  array $custNos
     *
     * @return int
     */
    public function batchCreate(int $idNo, array $custNos): int
    {
        $savedCustNos = $this->list($idNo);

        $total = count($savedCustNos) + count($custNos);
        if ($total > self::TOTAL_LIMIT) {
            throw new Exceptions\ExceedLimitException(
                ErrorCode::MSG_SAVE_COMPANY_EXCEED_LIMIT_ERROR,
                ErrorCode::CODE_SAVE_COMPANY_EXCEED_LIMIT_ERROR
            );
        }

        $validCustNos = array_filter($custNos, function ($custNo) use ($savedCustNos) {
            return !in_array($custNo, $savedCustNos);
        });
        if (empty($validCustNos)) {
            return 0;
        }

        $recordCount = $this->interestCompanyRepository->insertMany($idNo, $validCustNos);
        $this->cache->forget($this->getListCacheKey($idNo));

        return $recordCount;
    }

    /**
     * 多筆取消儲存公司
     *
     * @param  int $idNo
     * @param  array $custNos
     *
     * @return int
     */
    public function batchDelete(int $idNo, array $custNos): int
    {
        $savedCustNos = $this->list($idNo);
        $validCustNos = array_intersect($savedCustNos, $custNos);
        if (count($validCustNos) === 0) {
            return 0;
        }

        $recordCount = $this->interestCompanyRepository->deleteMany($idNo, $validCustNos);
        if ($recordCount > 0) {
            $this->cache->forget($this->getListCacheKey($idNo));
            $this->cache->forget($this->getListCacheKey($idNo, InterestCompanyRepository::FLAG_LIST_SUBSCRIBED));
        }

        return $recordCount;
    }

    /**
     * 多筆訂閱新工作通知
     *
     * @param  int $idNo
     * @param  array $custNos
     *
     * @return int
     */
    public function batchSubscribe(int $idNo, array $custNos): int
    {
        $savedCustNos = $this->list($idNo);
        $subscribedCustNos = $this->list($idNo, InterestCompanyRepository::FLAG_LIST_SUBSCRIBED);

        $validCustNos = array_filter($custNos, function ($custNo) use ($savedCustNos, $subscribedCustNos) {
            $saved = in_array($custNo, $savedCustNos);
            $subscribed = in_array($custNo, $subscribedCustNos);

            return $saved && !$subscribed;
        });
        if (empty($validCustNos)) {
            return 0;
        }

        $recordCount = $this->interestCompanyRepository->updateNotify($idNo, $validCustNos, InterestCompanyRepository::ACTION_SUBSCRIBE);
        $this->cache->forget($this->getListCacheKey($idNo, InterestCompanyRepository::FLAG_LIST_SUBSCRIBED));

        return $recordCount;
    }

    /**
     * 多筆取消訂閱新工作通知
     *
     * @param  int $idNo
     * @param  array $custNos
     *
     * @return int
     */
    public function batchUnsubscribe(int $idNo, array $custNos): int
    {
        $subscribedCustNos = $this->list($idNo, InterestCompanyRepository::FLAG_LIST_SUBSCRIBED);

        $validCustNos = array_filter($custNos, function ($custNo) use ($subscribedCustNos) {
            return in_array($custNo, $subscribedCustNos);
        });
        if (empty($validCustNos)) {
            return 0;
        }

        $recordCount = $this->interestCompanyRepository->updateNotify($idNo, $validCustNos, InterestCompanyRepository::ACTION_UNSUBSCRIBE);
        $this->cache->forget($this->getListCacheKey($idNo, InterestCompanyRepository::FLAG_LIST_SUBSCRIBED));

        return $recordCount;
    }

    /**
     * @param  int $idNo
     * @param  int $subscriptionFlag 0:儲存公司列表 1:已訂閱列表
     *
     * @return string
     */
    private function getListCacheKey(int $idNo, int $subscriptionFlag = InterestCompanyRepository::FLAG_LIST_ALL): string
    {
        if ($subscriptionFlag) {
            return self::SUBSCRIBED_LIST_CACHE_KEY . $idNo;
        }

        return self::LIST_CACHE_KEY . $idNo;
    }
}
