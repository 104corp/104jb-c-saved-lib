<?php

declare(strict_types=1);

namespace Tests\Unit;

use Corp104\Jbc\Saved\Exceptions\ErrorCode;
use Corp104\Jbc\Saved\Exceptions\ExceedLimitException;
use Corp104\Jbc\Saved\Repositories\NsBuffetRepository;
use Corp104\Jbc\Saved\Services\SavedJobService;
use PHPUnit\Framework\TestCase;
use Illuminate\Contracts\Cache\Repository as Cache;

class SavedJobServiceTest extends TestCase
{
    public function testListWithCacheDataShouldGetExpected()
    {
        $idNo = 123;
        $cacheKey = SavedJobService::LIST_CACHE_KEY . $idNo;
        $expected = [
            456456,
            789789,
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
        $this->cache = $mockCache;

        $target = new SavedJobService($this->createMock(NsBuffetRepository::class), $mockCache);
        $actual = $target->list($idNo);

        $this->assertSame($expected, $actual);
    }

    public function testListWithoutCacheDataShouldGetExpected()
    {
        $idNo = 123;
        $cacheKey = SavedJobService::LIST_CACHE_KEY . $idNo;
        $expected = [
            123123,
        ];
        $mockCache = $this->createMock(Cache::class);
        $mockCache->expects($this->once())
            ->method('has')
            ->with($cacheKey)
            ->willReturn(false);
        $mockCache->expects($this->once())
            ->method('put')
            ->with($cacheKey);

        $mockNsBuffetRepository = $this->createMock(NsBuffetRepository::class);
        $mockNsBuffetRepository->expects($this->once())
            ->method('findByIdNo')
            ->with($idNo)
            ->willReturn($expected);

        $target = new SavedJobService($mockNsBuffetRepository, $mockCache);
        $actual = $target->list($idNo);

        $this->assertSame($expected, $actual);
    }

    public function testBatchCreateWithTotalExceedLimitShouldThrowException()
    {
        $idNo = 123;
        $cacheKey = SavedJobService::LIST_CACHE_KEY . $idNo;

        $mockCache = $this->createMock(Cache::class);
        $mockCache->expects($this->once())
            ->method('has')
            ->with($cacheKey)
            ->willReturn(true);
        $mockCache->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn(range(1, 200));

        $this->expectException(ExceedLimitException::class);
        $this->expectExceptionMessage(ErrorCode::MSG_SAVE_JOB_EXCEED_LIMIT_ERROR);

        $target = new SavedJobService($this->createMock(NsBuffetRepository::class), $mockCache);
        $target->batchCreate($idNo, [
            [
                'jobNo' => 123,
                'custNo' => 456,
            ]
        ]);
    }

    public function testBatchCreateWithDuplicatedJobsShouldReturnZero()
    {
        $idNo = 123;
        $cacheKey = SavedJobService::LIST_CACHE_KEY . $idNo;
        $existedSavedJobNo = 123123;

        $mockCache = $this->createMock(Cache::class);
        $mockCache->expects($this->once())
            ->method('has')
            ->with($cacheKey)
            ->willReturn(false);

        $mockNsBuffetRepository = $this->createMock(NsBuffetRepository::class);
        $mockNsBuffetRepository->expects($this->once())
            ->method('findByIdNo')
            ->with($idNo)
            ->willReturn([$existedSavedJobNo]);

        $target = new SavedJobService($mockNsBuffetRepository, $mockCache);
        $actual = $target->batchCreate($idNo, [
            [
                'jobNo' => $existedSavedJobNo,
                'custNo' => 456,
            ]
        ]);

        $this->assertSame(0, $actual);
    }

    public function testBatchCreateWithValidJobsShouldClearCacheAfterCreate()
    {
        $idNo = 123;
        $cacheKey = SavedJobService::LIST_CACHE_KEY . $idNo;
        $validJobs = [
            [
                'jobNo' => 123123,
                'custNo' => 456,
            ]
        ];

        $mockCache = $this->createMock(Cache::class);
        $mockCache->expects($this->once())
            ->method('has')
            ->with($cacheKey)
            ->willReturn(false);
        $mockCache->expects($this->once())
            ->method('forget')
            ->with($cacheKey);

        $mockNsBuffetRepository = $this->createMock(NsBuffetRepository::class);
        $mockNsBuffetRepository->expects($this->once())
            ->method('createMany')
            ->with($idNo, $validJobs)
            ->willReturn(1);

        $target = new SavedJobService($mockNsBuffetRepository, $mockCache);
        $actual = $target->batchCreate($idNo, $validJobs);

        $this->assertSame(1, $actual);
    }

    public function testBatchDeleteWithValidJobNosShouldClearCacheAfterDelete()
    {
        $idNo = 123;
        $cacheKey = SavedJobService::LIST_CACHE_KEY . $idNo;

        $mockNsBuffetRepository = $this->createMock(NsBuffetRepository::class);
        $mockNsBuffetRepository->expects($this->once())
            ->method('deleteMany')
            ->with($idNo, [123, 456, 789])
            ->willReturn(3);

        $mockCache = $this->createMock(Cache::class);
        $mockCache->expects($this->once())
            ->method('has')
            ->with($cacheKey)
            ->willReturn(true);
        $mockCache->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn([123, 456, 789]);
        $mockCache->expects($this->once())
            ->method('forget')
            ->with($cacheKey);

        $target = new SavedJobService($mockNsBuffetRepository, $mockCache);
        $actual = $target->batchDelete($idNo, [123, 456, 789]);

        $this->assertSame(3, $actual);
    }

    public function testBatchDeleteWithInvalidJobNosShouldReturnZero()
    {
        $idNo = 123;
        $cacheKey = SavedJobService::LIST_CACHE_KEY . $idNo;

        $mockNsBuffetRepository = $this->createMock(NsBuffetRepository::class);

        $mockCache = $this->createMock(Cache::class);
        $mockCache->expects($this->once())
            ->method('has')
            ->with($cacheKey)
            ->willReturn(true);
        $mockCache->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn([321, 654, 987]);

        $target = new SavedJobService($mockNsBuffetRepository, $mockCache);
        $actual = $target->batchDelete($idNo, [123, 456, 789]);

        $this->assertSame(0, $actual);
    }
}
