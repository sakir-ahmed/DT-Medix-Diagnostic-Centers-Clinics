<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use App\Models\Tenant;
use Spatie\Permission\PermissionRegistrar;

/**
 * Holds the tenant (and the branches the current user may see) for the
 * current request, job or command. Registered as a scoped singleton.
 */
final class TenantContext
{
    private ?Tenant $tenant = null;

    /**
     * Branch IDs the current user may access. Null means all branches.
     *
     * @var list<int>|null
     */
    private ?array $branchIds = null;

    private ?int $currentBranchId = null;

    public function set(?Tenant $tenant): void
    {
        $this->tenant = $tenant;
        $this->branchIds = null;
        $this->currentBranchId = null;

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant?->id);
    }

    public function tenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function id(): ?int
    {
        return $this->tenant?->id;
    }

    public function check(): bool
    {
        return $this->tenant !== null;
    }

    /**
     * @param  list<int>|null  $branchIds
     */
    public function restrictBranches(?array $branchIds): void
    {
        $this->branchIds = $branchIds;
    }

    /**
     * @return list<int>|null
     */
    public function branchIds(): ?array
    {
        return $this->branchIds;
    }

    public function setCurrentBranch(?int $branchId): void
    {
        $this->currentBranchId = $branchId;
    }

    public function currentBranchId(): ?int
    {
        return $this->currentBranchId;
    }

    /**
     * Run a callback inside the context of a tenant, restoring the previous one.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function run(?Tenant $tenant, callable $callback): mixed
    {
        $previous = $this->tenant;
        $previousBranches = $this->branchIds;
        $previousBranch = $this->currentBranchId;

        $this->set($tenant);

        try {
            return $callback();
        } finally {
            $this->set($previous);
            $this->branchIds = $previousBranches;
            $this->currentBranchId = $previousBranch;
        }
    }

    /**
     * Prefix a cache or Redis key with the tenant ID.
     */
    public function key(string $key): string
    {
        return 'tenant:'.($this->id() ?? 'central').':'.$key;
    }

    /**
     * Prefix a storage path with the tenant directory.
     */
    public function path(string $path = ''): string
    {
        return 'tenants/'.($this->id() ?? 'central').($path === '' ? '' : '/'.ltrim($path, '/'));
    }
}
