<?php

namespace App\Repositories\Contracts;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Support\Collection;

interface AssetRepository
{
    /**
     * Find asset by ID
     */
    public function findById(int $id): ?Asset;

    /**
     * Find asset by user and symbol
     */
    public function findByUserAndSymbol(User $user, string $symbol): ?Asset;

    /**
     * Find asset by user and symbol with lock for update
     */
    public function findByUserAndSymbolForUpdate(User $user, string $symbol): ?Asset;

    /**
     * Get all assets for a user
     */
    public function getByUser(User $user): Collection;

    /**
     * Create or update asset for user
     */
    public function createOrUpdate(User $user, string $symbol, array $data): Asset;

    /**
     * Update asset amounts
     */
    public function updateAmounts(Asset $asset, string $amount, string $lockedAmount): bool;

    /**
     * Lock amount for order
     */
    public function lockAmount(Asset $asset, string $amount): bool;

    /**
     * Unlock amount from order
     */
    public function unlockAmount(Asset $asset, string $amount): bool;

    /**
     * Decrement available amount
     */
    public function decrementAmount(Asset $asset, string $amount): bool;

    /**
     * Increment available amount
     */
    public function incrementAmount(Asset $asset, string $amount): bool;

    /**
     * Decrement locked amount
     */
    public function decrementLockedAmount(Asset $asset, string $amount): bool;
}
