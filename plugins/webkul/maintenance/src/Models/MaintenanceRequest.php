<?php

namespace Webkul\Maintenance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Webkul\Maintenance\Database\Factories\MaintenanceRequestFactory;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

class MaintenanceRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'maintenance_requests';

    protected $fillable = [
        'repeat_interval',
        'name',
        'priority',
        'state',
        'maintenance_type',
        'instruction_type',
        'instruction_google_slide',
        'repeat_unit',
        'repeat_type',
        'requested_at',
        'closed_at',
        'repeat_until',
        'duration',
        'description',
        'instruction_text',
        'recurring_maintenance',
        'scheduled_at',
        'equipment_id',
        'stage_id',
        'category_id',
        'owner_user_id',
        'user_id',
        'maintenance_team_id',
        'company_id',
        'creator_id',
        'deleted_at',
    ];

    protected $casts = [
        'repeat_interval'       => 'integer',
        'requested_at'          => 'date',
        'closed_at'             => 'date',
        'repeat_until'          => 'date',
        'duration'              => 'float',
        'recurring_maintenance' => 'boolean',
        'scheduled_at'          => 'datetime',
    ];

    public function getModelTitle(): string
    {
        return __('maintenance::models/maintenance-request.title');
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'equipment_id')->withTrashed();
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class, 'stage_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(EquipmentCategory::class, 'category_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'maintenance_team_id')->withTrashed();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    protected static function newFactory(): MaintenanceRequestFactory
    {
        return MaintenanceRequestFactory::new();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $request): void {
            $authUser = Auth::user();

            $request->creator_id ??= $authUser?->id;
            $request->company_id ??= $authUser?->default_company_id;
            $request->owner_user_id ??= $authUser?->id;
            $request->state ??= 'new';
        });
    }
}
