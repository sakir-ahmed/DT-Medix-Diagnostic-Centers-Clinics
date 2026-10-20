<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Holds the branches the current user may see and the branch they are
 * working in for the current request, job or command. Registered as a
 * scoped singleton. Owners and users with "branches.all" are not
 * restricted (branchIds() is null).
 */
final class BranchContext
{
    /**
     * @var list<int>|null
     */
    private ?array $branchIds = null;

    private ?int $currentBranchId = null;

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

    public function reset(): void
    {
        $this->branchIds = null;
        $this->currentBranchId = null;
    }

    /**
     * Run a callback without branch restriction (jobs, commands, tests),
     * restoring the previous restriction afterwards.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function unrestricted(callable $callback): mixed
    {
        $previousBranches = $this->branchIds;
        $previousBranch = $this->currentBranchId;
        $this->branchIds = null;

        try {
            return $callback();
        } finally {
            $this->branchIds = $previousBranches;
            $this->currentBranchId = $previousBranch;
        }
    }
}
