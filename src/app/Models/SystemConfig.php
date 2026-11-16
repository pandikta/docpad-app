<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class SystemConfig extends Model
{
    protected $table = 'mst_system_configs';

    protected $fillable = [
        'key',
        'value',
        'description',
    ];

    public static function getInt(string $key, int $default = 0): int
    {
        if (! Schema::hasTable('mst_system_configs')) {
            return $default;
        }

        $value = static::where('key', $key)->value('value');

        return $value !== null && is_numeric($value) ? (int) $value : $default;
    }

    public static function setValue(string $key, ?string $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );
    }
}
