<?php

namespace App\Observers;

use App\Models\FeeStructure;
use App\Models\FeeStructureImmutableAudit;
use Illuminate\Support\Facades\Schema;

class FeeStructureObserver
{
    public function created(FeeStructure $structure): void
    {
        $this->audit($structure, 'created', null, $structure->getAttributes());
    }

    public function updated(FeeStructure $structure): void
    {
        $dirty = $structure->getChanges();
        unset($dirty['updated_at']);

        if (empty($dirty)) {
            return;
        }

        $old = [];
        foreach (array_keys($dirty) as $key) {
            $old[$key] = $structure->getOriginal($key);
        }

        $this->audit($structure, 'updated', $old, $dirty);
    }

    public function deleted(FeeStructure $structure): void
    {
        $this->audit($structure, 'deleted', $structure->getAttributes(), null);
    }

    private function audit(FeeStructure $structure, string $event, ?array $oldValues, ?array $newValues): void
    {
        if (!Schema::hasTable('fee_structure_immutable_audits')) {
            return;
        }

        FeeStructureImmutableAudit::create([
            'auditable_type' => FeeStructure::class,
            'auditable_id' => $structure->id,
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => [
                'source' => 'observer',
                'version_no' => $structure->version_no ?? 1,
                'version_status' => $structure->version_status ?? 'active',
            ],
            'user_id' => auth()->id(),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'created_at' => now(),
        ]);
    }
}
