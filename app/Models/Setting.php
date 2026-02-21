<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'category',
        'key',
        'value',
        'data_type',
        'description',
        'is_editable',
    ];

    protected $casts = [
        'is_editable' => 'boolean',
    ];

    // Relationships
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    // Helper Methods
    public static function get($key, $default = null, $branchId = null)
    {
        $setting = self::where('key', $key);
        
        if ($branchId) {
            $setting->where('branch_id', $branchId);
        }
        
        $result = $setting->first();
        
        if (!$result) {
            return $default;
        }
        
        return self::castValue($result->value, $result->data_type);
    }

    public static function set($key, $value, $branchId = null)
    {
        return self::updateOrCreate(
            ['key' => $key, 'branch_id' => $branchId],
            ['value' => $value]
        );
    }

    private static function castValue($value, $type)
    {
        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'number':
                return is_numeric($value) ? $value + 0 : $value;
            case 'json':
                return json_decode($value, true);
            default:
                return $value;
        }
    }
}
