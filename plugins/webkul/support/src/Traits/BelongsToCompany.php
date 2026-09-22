<?php

namespace Webkul\Support\Traits;

use Webkul\Support\Exceptions\CrossCompanyException;
use Webkul\Support\Models\Scopes\CompanyScope;
use Webkul\Support\Services\CompanyContext;

trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::addGlobalScope(new CompanyScope);

        static::creating(function ($model) {
            if (empty($model->company_id) && static::autoAssignsCompany()) {
                $model->company_id ??= app(CompanyContext::class)->currentId();
            }

            static::assertCompanyAllowed($model->company_id);
        });

        static::updating(function ($model) {
            if ($model->isDirty('company_id')) {
                static::assertCompanyAllowed($model->company_id);
            }
        });
    }

    public static function autoAssignsCompany(): bool
    {
        return true;
    }

    protected static function assertCompanyAllowed(mixed $companyId): void
    {
        if (blank($companyId) || (app()->runningInConsole() && ! app()->runningUnitTests())) {
            return;
        }

        $context = app(CompanyContext::class);

        if (! $context->internalUser() || $context->seesAllCompanies()) {
            return;
        }

        $companyId = (int) $companyId;

        if (! in_array($companyId, $context->allowedIds(), true)) {
            throw CrossCompanyException::forCompany($companyId);
        }
    }
}
