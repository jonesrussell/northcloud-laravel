<?php

declare(strict_types=1);

namespace JonesRussell\NorthCloud\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait IsAdministrator
{
    public function initializeIsAdministrator(): void
    {
        $this->mergeCasts([
            'is_admin' => 'boolean',
        ]);
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function scopeAdmin(Builder $query): Builder
    {
        return $query->where('is_admin', true);
    }

    public function scopeNonAdmin(Builder $query): Builder
    {
        return $query->where('is_admin', false);
    }
}
