<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $faculty
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Collection<User> $users
 */
#[Fillable(['code', 'name', 'faculty'])]
class StudyProgram extends Model
{
    /**
     * Users dalam program studi ini
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
