<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class JobDiscipline
 *
 * A job discipline is a specific area of work within a job family.
 * Examples: Software Engineering, Marketing, Sales, HR, Finance.
 *
 * @property int $id
 * @property int $organization_id
 * @property int $job_family_id
 * @property string $name
 * @property string|null $description
 * @property string|null $slug
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
final class JobDiscipline extends Model
{
    /** @use HasFactory<\Database\Factories\JobDisciplineFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'job_disciplines';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'job_family_id',
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
     * Get the organization associated with the job discipline.
     *
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the job family associated with the job discipline.
     *
     * @return BelongsTo<JobFamily, $this>
     */
    public function jobFamily(): BelongsTo
    {
        return $this->belongsTo(JobFamily::class);
    }

    /**
     * Get the job levels associated with the job discipline.
     *
     * @return HasMany<JobLevel, $this>
     */
    public function jobLevels(): HasMany
    {
        return $this->hasMany(JobLevel::class, 'job_discipline_id');
    }
}
