<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class JobLevel
 *
 * A job level is a specific level of work within a job discipline.
 * Examples: Entry level, Mid level, Senior level.
 *
 * @property int $id
 * @property int $organization_id
 * @property int $job_discipline_id
 * @property string $name
 * @property string|null $description
 * @property string|null $slug
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
final class JobLevel extends Model
{
    /** @use HasFactory<\Database\Factories\JobLevelFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'job_levels';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'job_discipline_id',
        'name',
        'description',
        'slug',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [];
    }

    /**
     * Get the organization associated with the job level.
     *
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the job discipline associated with the job level.
     *
     * @return BelongsTo<JobDiscipline, $this>
     */
    public function jobDiscipline(): BelongsTo
    {
        return $this->belongsTo(JobDiscipline::class);
    }
}
