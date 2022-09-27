<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Corp104\Jbc\Saved\Exceptions\ErrorCode;
use Corp104\Jbc\Saved\Exceptions\ExceedLimitException;
use Corp104\Jbc\Saved\Repositories\InterestCompanyRepository;
use Corp104\Jbc\Saved\Services\SavedCompanyService;
use PHPUnit\Framework\TestCase;
use Illuminate\Contracts\Cache\Repository as Cache;

class SavedCompanyServiceTest extends TestCase
{
    public function testListWithCacheDataShouldGetExpected()
    {
        $idNo = 123;
        $cacheKey = SavedCompanyService::LIST_CACHE_KEY . $idNo;
        $expected = [
            [
                'custNo' => 456456,
                'inputDate' => '2022-09-27 15:05:00',
                'notify' => 1,
            ],
        ];
        $mockCache = $this->createMock(Cache::class);
        $mockCache->expects($this->once())
            ->method('has')
            ->with($cacheKey)
            ->willReturn(true);
        $mockCache->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn($expected);

        $target = new SavedCompanyService($this->createMock(InterestCompanyRepository::class), $mockCache);
        $actual = $target->list($idNo);

        $this->assertSame($expected, $actual);
    }

    public function testSubscribedListWithCacheDataShouldGetExpected()
    {
        $idNo = 123;
        $cacheKey = SavedCompanyService::SUBSCRIBED_LIST_CACHE_KEY . $idNo;
        $expected = [
            [
                'custNo' => 456456,
                'inputDate' => '2022-09-27 15:05:00',
                'notify' => 1,
            ],
        ];
        $mockCache = $this->createMock(Cache::class);
        $mockCache->expects($this->once())
            ->method('has')
            ->with($cacheKey)
            ->willReturn(true);
        $mockCache->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn($expected);

        $target = new SavedCompanyService($this->createMock(InterestCompanyRepository::class), $mockCache);
        $actual = $target->list($idNo, InterestCompanyRepository::FLAG_LIST_SUBSCRIBED);

        $this->assertSame($expected, $actual);
    }

    public function testListWithoutCacheDataShouldGetExpected()
    {
        $idNo = 123;
        $cacheKey = SavedCompanyService::LIST_CACHE_KEY . $idNo;
        $expected = [
            [
                'custNo' => 456456,
                'inputDate' => '2022-09-27 15:05:00',
                'notify' => 1,
            ],
        ];
        $mockCache = $this->createMock(Cache::class);
        $mockCache->expects($this->once())
            ->method('has')
            ->with($cacheKey)
            ->willReturn(false);
        $mockCache->expects($this->once())
            ->method('put')
            ->with($cacheKey);

        $mockRepository = $this->createMock(InterestCompanyRepository::class);
        $mockRepository->expects($this->once())
            ->method('findByIdNo')
            ->with($idNo, InterestCompanyRepository::FLAG_LIST_ALL)
            ->willReturn([
                [
                    'custno' => 456456,
                    'input_date' => '2022-09-27 15:05:00',
                    'notify' => 1,
                ],
            ]);

        $target = new SavedCompanyService($mockRepository, $mockCache);
        $actual = $target->list($idNo);

        $this->assertSame($expected, $actual);
    }

    public function testSubscribedListWithoutCacheDataShouldGetExpected()
    {
        $idNo = 123;
        $cacheKey = SavedCompanyService::SUBSCRIBED_LIST_CACHE_KEY . $idNo;
        $expected = [
            [
                'custNo' => 456456,
                'inputDate' => '2022-09-27 15:05:00',
                'notify' => 1,
            ],
        ];
        $mockCache = $this->createMock(Cache::class);
        $mockCache->expects($this->once())
            ->method('has')
            ->with($cacheKey)
            ->willReturn(false);
        $mockCache->expects($this->once())
            ->method('put')
            ->with($cacheKey);

        $mockRepository = $this->createMock(InterestCompanyRepository::class);
        $mockRepository->expects($this->once())
            ->method('findByIdNo')
            ->with($idNo, InterestCompanyRepository::FLAG_LIST_SUBSCRIBED)
            ->willReturn([
                [
                    'custno' => 456456,
                    'input_date' => '2022-09-27 15:05:00',
                    'notify' => 1,
                ],
            ]);

        $target = new SavedCompanyService($mockRepository, $mockCache);
        $actual = $target->list($idNo, InterestCompanyRepository::FLAG_LIST_SUBSCRIBED);

        $this->assertSame($expected, $actual);
    }

    public function testBatchCreateWithTotalExceedLimitShouldThrowException()
    {
        $idNo = 123;
        $cacheKey = SavedCompanyService::LIST_CACHE_KEY . $idNo;

        $mockCache = $this->createMock(Cache::class);
        $mockCache->expects($this->once())
            ->method('has')
            ->with($cacheKey)
            ->willReturn(true);
        $mockCache->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn(array_map(function ($custNo) {
                return [
                    'custNo' => $custNo
                ];
            }, range(1, 200)));

        $this->expectException(ExceedLimitException::class);
        $this->expectExceptionMessage(ErrorCode::MSG_SAVE_COMPANY_EXCEED_LIMIT_ERROR);

        $target = new SavedCompanyService($this->createMock(InterestCompanyRepository::class), $mockCache);
        $target->batchCreate($idNo, [123]);
    }

    public function testBatchCreateWithDuplicatedCustnosShouldReturnZero()
    {
        $idNo = 123;
        $cacheKey = SavedCompanyService::LIST_CACHE_KEY . $idNo;
        $existedSavedCustNo = 123123;

        $mockCache = $this->createMock(Cache::class);
        $mockCache->expects($this->once())
            ->method('has')
            ->with($cacheKey)
            ->willReturn(true);
        $mockCache->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn([
                [
                    'custNo' => $existedSavedCustNo,
                ]
            ]);

        $target = new SavedCompanyService($this->createMock(InterestCompanyRepository::class), $mockCache);
        $actual = $target->batchCreate($idNo, [$existedSavedCustNo]);

        $this->assertSame(0, $actual);
    }

    public function testBatchCreateWithValidCustnosShouldClearCacheAfterCreate()
    {
        $idNo = 123;
        $cacheKey = SavedCompanyService::LIST_CACHE_KEY . $idNo;
        $validJobs = [
            123,
            456,
        ];

        $mockCache = $this->createMock(Cache::class);
        $mockCache->expects($this->once())
            ->method('has')
            ->with($cacheKey)
            ->willReturn(false);
        $mockCache->expects($this->once())
            ->method('forget')
            ->with($cacheKey);

        $mockRepository = $this->createMock(InterestCompanyRepository::class);
        $mockRepository->expects($this->once())
            ->method('insertMany')
            ->with($idNo, $validJobs)
            ->willReturn(2);

        $target = new SavedCompanyService($mockRepository, $mockCache);
        $actual = $target->batchCreate($idNo, $validJobs);

        $this->assertSame(2, $actual);
    }

    public function testBatchDeleteWithValidCustnosShouldClearCacheAfterDelete()
    {
        $idNo = 123;
        $cacheKey = SavedCompanyService::LIST_CACHE_KEY . $idNo;
        $subscribedCacheKey = SavedCompanyService::SUBSCRIBED_LIST_CACHE_KEY . $idNo;
        $validCustNos = [
            123,
            456,
        ];

        $mockCache = $this->createMock(Cache::class);
        $mockCache->expects($this->once())
            ->method('has')
            ->with($cacheKey)
            ->willReturn(true);
        $mockCache->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn([
                ['custNo' => 123,],
                ['custNo' => 456,],
            ]);
        $mockCache->expects($this->exactly(2))
            ->method('forget')
            ->withConsecutive([$cacheKey], [$subscribedCacheKey]);

        $mockRepository = $this->createMock(InterestCompanyRepository::class);
        $mockRepository->expects($this->once())
            ->method('deleteMany')
            ->with($idNo, $validCustNos)
            ->willReturn(2);

        $target = new SavedCompanyService($mockRepository, $mockCache);
        $actual = $target->batchDelete($idNo, $validCustNos);

        $this->assertSame(2, $actual);
    }

    public function testBatchDeleteWithInvalidCustnosShouldReturnZero()
    {
        $idNo = 123;
        $cacheKey = SavedCompanyService::LIST_CACHE_KEY . $idNo;

        $mockCache = $this->createMock(Cache::class);
        $mockCache->expects($this->once())
            ->method('has')
            ->with($cacheKey)
            ->willReturn(true);
        $mockCache->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn([321, 654]);

        $target = new SavedCompanyService($this->createMock(InterestCompanyRepository::class), $mockCache);
        $actual = $target->batchDelete($idNo, [123, 456]);

        $this->assertSame(0, $actual);
    }

    public function testBatchSubscribeWithSavedCustnosShouldGetExpected()
    {
        $idNo = 123;
        $cacheKey = SavedCompanyService::LIST_CACHE_KEY . $idNo;
        $subscribedCacheKey = SavedCompanyService::SUBSCRIBED_LIST_CACHE_KEY . $idNo;
        $mockCache = $this->createMock(Cache::class);
        $mockCache->expects($this->exactly(2))
            ->method('has')
            ->withConsecutive([$cacheKey], [$subscribedCacheKey])
            ->willReturnOnConsecutiveCalls(true, true);
        $mockCache->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive([$cacheKey], [$subscribedCacheKey])
            ->willReturnOnConsecutiveCalls([
                ['custNo' => 456456,],
                ['custNo' => 789789,],
            ], []);

        $mockRepository = $this->createMock(InterestCompanyRepository::class);
        $mockRepository->expects($this->once())
            ->method('updateNotify')
            ->with($idNo, [456456], InterestCompanyRepository::ACTION_SUBSCRIBE)
            ->willReturn(1);

        $target = new SavedCompanyService($mockRepository, $mockCache);
        $actual = $target->batchSubscribe($idNo, [456456]);

        $this->assertSame(1, $actual);
    }

    public function testBatchSubscribeWithSubscribedCustnosShouldReturnZero()
    {
        $idNo = 123;
        $cacheKey = SavedCompanyService::LIST_CACHE_KEY . $idNo;
        $subscribedCacheKey = SavedCompanyService::SUBSCRIBED_LIST_CACHE_KEY . $idNo;
        $mockCache = $this->createMock(Cache::class);
        $mockCache->expects($this->exactly(2))
            ->method('has')
            ->withConsecutive([$cacheKey], [$subscribedCacheKey])
            ->willReturnOnConsecutiveCalls(true, true);
        $mockCache->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive([$cacheKey], [$subscribedCacheKey])
            ->willReturnOnConsecutiveCalls([
                ['custNo' => 456456,],
                ['custNo' => 789789,],
            ], [
                ['custNo' => 456456,]
            ]);

        $target = new SavedCompanyService($this->createMock(InterestCompanyRepository::class), $mockCache);
        $actual = $target->batchSubscribe($idNo, [456456]);

        $this->assertSame(0, $actual);
    }

    public function testBatchSubscribeWithCustnosNotSavedShouldReturnZero()
    {
        $idNo = 123;
        $cacheKey = SavedCompanyService::LIST_CACHE_KEY . $idNo;
        $subscribedCacheKey = SavedCompanyService::SUBSCRIBED_LIST_CACHE_KEY . $idNo;
        $savedCustnos = [
            ['custNo' => 456456,],
            ['custNo' => 789789,],
        ];

        $mockCache = $this->createMock(Cache::class);
        $mockCache->expects($this->exactly(2))
            ->method('has')
            ->withConsecutive([$cacheKey], [$subscribedCacheKey])
            ->willReturnOnConsecutiveCalls(true, true);
        $mockCache->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive([$cacheKey], [$subscribedCacheKey])
            ->willReturnOnConsecutiveCalls($savedCustnos, []);

        $target = new SavedCompanyService($this->createMock(InterestCompanyRepository::class), $mockCache);
        $actual = $target->batchSubscribe($idNo, [321321]);

        $this->assertSame(0, $actual);
    }

    public function testBatchUnsubscribeWithValidCustnosShouldGetExpected()
    {
        $idNo = 123;
        $subscribedCacheKey = SavedCompanyService::SUBSCRIBED_LIST_CACHE_KEY . $idNo;
        $subscribedCustnos = [
            ['custNo' => 456456,],
            ['custNo' => 789789,],
        ];

        $mockCache = $this->createMock(Cache::class);
        $mockCache->expects($this->once())
            ->method('has')
            ->with($subscribedCacheKey)
            ->willReturn(true);
        $mockCache->expects($this->once())
            ->method('get')
            ->with($subscribedCacheKey)
            ->willReturn($subscribedCustnos);

        $mockRepository = $this->createMock(InterestCompanyRepository::class);
        $mockRepository->expects($this->once())
            ->method('updateNotify')
            ->with($idNo, [456456], InterestCompanyRepository::ACTION_UNSUBSCRIBE)
            ->willReturn(1);

        $target = new SavedCompanyService($mockRepository, $mockCache);
        $actual = $target->batchUnsubscribe($idNo, [456456]);

        $this->assertSame(1, $actual);
    }

    public function testBatchUnsubscribeWithCustnosNotSubscribedShouldReturnZero()
    {
        $idNo = 123;
        $subscribedCacheKey = SavedCompanyService::SUBSCRIBED_LIST_CACHE_KEY . $idNo;
        $subscribedCustnos = [
            ['custNo' => 456456,],
            ['custNo' => 789789,],
        ];

        $mockCache = $this->createMock(Cache::class);
        $mockCache->expects($this->once())
            ->method('has')
            ->with($subscribedCacheKey)
            ->willReturn(true);
        $mockCache->expects($this->once())
            ->method('get')
            ->with($subscribedCacheKey)
            ->willReturn($subscribedCustnos);

        $target = new SavedCompanyService($this->createMock(InterestCompanyRepository::class), $mockCache);
        $actual = $target->batchUnsubscribe($idNo, [321321]);

        $this->assertSame(0, $actual);
    }
}
