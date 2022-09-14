<?php

declare(strict_types=1);

namespace Corp104\Jbc\Saved\Exceptions;

class ErrorCode
{
    // 儲存工作
    public const MSG_SAVE_JOB_EXCEED_LIMIT_ERROR = '儲存工作數量超過 200 筆';
    public const CODE_SAVE_JOB_EXCEED_LIMIT_ERROR = 37001;

    // 儲存公司
    public const MSG_SAVE_COMPANY_EXCEED_LIMIT_ERROR = '儲存公司數量超過 200 筆';
    public const CODE_SAVE_COMPANY_EXCEED_LIMIT_ERROR = 37101;
}
