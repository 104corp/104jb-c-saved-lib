<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Corp104\Jbc\Saved\Exceptions\ErrorCode;
use Corp104\Jbc\Saved\Exceptions\ExceedLimitException;
use Corp104\Jbc\Saved\Repositories\NsBuffetRepository;
use Corp104\Jbc\Saved\Services\SavedJobService;
use Corp104\Jbc\Search\Job\JobSearch;
use PHPUnit\Framework\TestCase;
use Illuminate\Contracts\Cache\Repository as Cache;

class SavedJobServiceTest extends TestCase
{
    public function testListWithCacheDataShouldGetExpected()
    {
        $idNo = 123;
        $cacheKey = SavedJobService::LIST_CACHE_KEY . $idNo;
        $expected = [
            [
                'jobNo' => 456456,
                'inputDate' => '2022-09-27 13:35:00'
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

        $target = new SavedJobService(
            $this->createMock(NsBuffetRepository::class),
            $this->createMock(JobSearch::class),
            $mockCache,
        );
        $actual = $target->list($idNo);

        $this->assertSame($expected, $actual);
    }

    public function testListWithoutCacheDataShouldGetExpected()
    {
        $idNo = 123;
        $cacheKey = SavedJobService::LIST_CACHE_KEY . $idNo;
        $jobNos = [123123];
        $expected = [
            [
                'jobNo' => 123123,
                'inputDate' => '2022-09-27 13:35:00'
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

        $mockNsBuffetRepository = $this->createMock(NsBuffetRepository::class);
        $mockNsBuffetRepository->expects($this->once())
            ->method('findByIdNo')
            ->with($idNo)
            ->willReturn([
                [
                    'jobno' => 123123,
                    'input_date' => '2022-09-27 13:35:00'
                ],
            ]);

        $mockSearch = $this->createMock(JobSearch::class);
        $mockSearch->expects($this->once())
            ->method('onJobs')
            ->with($jobNos)
            ->willReturn($mockSearch);
        $mockSearch->expects($this->once())
            ->method('offJobs')
            ->with($jobNos)
            ->willReturn($mockSearch);
        $mockSearch->expects($this->exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls([['RETURNID' => 123123]], []);

        $target = new SavedJobService(
            $mockNsBuffetRepository,
            $mockSearch,
            $mockCache,
        );
        $actual = $target->list($idNo);

        $this->assertSame($expected, $actual);
    }

    public function testBatchCreateWithTotalExceedLimitShouldThrowException()
    {
        $idNo = 123;
        $cacheKey = SavedJobService::LIST_CACHE_KEY . $idNo;

        $cachedJobs = array_map(function ($jobNo) {
            return [
                'jobNo' => $jobNo,
            ];
        }, range(1, 200));
        $mockCache = $this->createMock(Cache::class);
        $mockCache->expects($this->once())
            ->method('has')
            ->with($cacheKey)
            ->willReturn(true);
        $mockCache->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn($cachedJobs);

        $this->expectException(ExceedLimitException::class);
        $this->expectExceptionMessage(ErrorCode::MSG_SAVE_JOB_EXCEED_LIMIT_ERROR);

        $target = new SavedJobService(
            $this->createMock(NsBuffetRepository::class),
            $this->createMock(JobSearch::class),
            $mockCache,
        );
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
            ->willReturn([
                ['jobno' => $existedSavedJobNo]
            ]);

        $target = new SavedJobService(
            $mockNsBuffetRepository,
            $this->createMock(JobSearch::class),
            $mockCache,
        );
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
            ->method('insertMany')
            ->with($idNo, $validJobs)
            ->willReturn(1);

        $target = new SavedJobService(
            $mockNsBuffetRepository,
            $this->createMock(JobSearch::class),
            $mockCache,
        );
        $actual = $target->batchCreate($idNo, $validJobs);

        $this->assertSame(1, $actual);
    }

    public function testBatchDeleteWithValidJobnosShouldClearCacheAfterDelete()
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
            ->method('forget')
            ->with($cacheKey);

        $target = new SavedJobService(
            $mockNsBuffetRepository,
            $this->createMock(JobSearch::class),
            $mockCache,
        );
        $actual = $target->batchDelete($idNo, [123, 456, 789]);

        $this->assertSame(3, $actual);
    }
}
