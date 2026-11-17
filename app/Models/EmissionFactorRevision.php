<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['emission_factor_id', 'changed_by', 'old_value', 'new_value', 'note', 'changed_at'])]
class EmissionFactorRevision extends Model
{
    protected function casts(): array { return ['changed_at' => 'datetime']; }
    public function factor(): BelongsTo { return $this->belongsTo(EmissionFactor::class, 'emission_factor_id'); }
}
