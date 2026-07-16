<?php

namespace App\Models\Sdm;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model for the divisions table.
 *
 * Stores company division/department data.
 * Uses a string-based relationship with Employee via the `division` column
 * (matching division name) rather than a traditional foreign key.
 *
 * @property int    $id
 * @property string $name
 * @property string|null $description
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read int $employees_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Sdm\Employee[] $employees
 */
class Division extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Get the employees belonging to this division.
     *
     * Uses a string-based relationship: employees.division = divisions.name.
     *
     * @return HasMany
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'division', 'name');
    }
}
