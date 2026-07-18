<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['program_id', 'code', 'name', 'description', 'year_level', 'semester_number', 'credits'])]
class Course extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'year_level' => 'integer',
            'semester_number' => 'integer',
            'credits' => 'integer',
        ];
    }

    /** @return BelongsTo<Program, $this> */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /** @return HasMany<CourseOffering, $this> */
    public function offerings(): HasMany
    {
        return $this->hasMany(CourseOffering::class);
    }
}
