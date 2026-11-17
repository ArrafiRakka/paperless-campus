<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'value', 'unit', 'source', 'source_year', 'description', 'updated_by'])]
class EmissionFactor extends Model
{
    public function revisions(): HasMany { return $this->hasMany(EmissionFactorRevision::class); }
}
