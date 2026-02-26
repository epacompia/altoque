<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommissionAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'commission_rule_id',
        'action',
        'old_values',
        'new_values',
        'changed_by',
        'ip_address',
        'notes'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * Relación con CommissionRule
     */
    public function commissionRule()
    {
        return $this->belongsTo(CommissionRule::class);
    }

    /**
     * Registrar un cambio
     */
    public static function log($action, $ruleId, $oldValues, $newValues, $changedBy, $ipAddress = null, $notes = null)
    {
        return self::create([
            'commission_rule_id' => $ruleId,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'changed_by' => $changedBy,
            'ip_address' => $ipAddress,
            'notes' => $notes
        ]);
    }
}
