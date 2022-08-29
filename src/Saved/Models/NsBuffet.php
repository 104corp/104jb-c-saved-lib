<?php

declare(strict_types=1);

namespace Corp104\Jbc\Saved\Models;

use Corp104\Jbc\Saved\Factories\NsBuffetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
