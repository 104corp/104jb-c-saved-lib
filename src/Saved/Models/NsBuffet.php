<?php

declare(strict_types=1);

namespace Corp104\Jbc\Saved\Models;

use Corp104\Jbc\Saved\Factories\NsBuffetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int id_no 使用者代碼
 * @property int jobno 職務代碼
 * @property int custno 公司代碼
 * @property int folder_no 儲存工作資料夾代碼
 * @property string memo 註記
 * @property Carbon input_date 輸入時間，格式 1900-01-01 00:00:00
 */
class NsBuffet extends Model
{
    use HasFactory;

    protected $connection = 'sc00009';

    protected $table = 'nsbuffet2';

    public $timestamps = false;

    protected static function newFactory()
    {
        return NsBuffetFactory::new();
    }
}
