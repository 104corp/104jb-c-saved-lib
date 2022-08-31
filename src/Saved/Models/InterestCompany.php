<?php

declare(strict_types=1);

namespace Corp104\Jbc\Saved\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int id_no 使用者代碼
 * @property int custno 公司代碼
 * @property int folder_no 存放資料夾代碼
 * @property int notify 是否訂閱新職務通知 0:否 1:訂閱
 * @property string memo 註記
 * @property Carbon input_date 輸入時間，格式 1900-01-01 00:00:00
 */
class InterestCompany extends Model
{
    use HasFactory;

    protected $connection = 'sc00009';

    protected $table = 'interest_company';

    protected $primaryKey = ['id_no', 'custno'];

    public $timestamps = false;

    public $incrementing = false;

    protected static function newFactory()
    {
        //
    }
}
