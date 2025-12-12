<?php

namespace App\Repositories;

use App\Models\Asset;
use App\Models\User;
use App\Repositories\Contracts\AssetRepository;
use Illuminate\Support\Collection;

class EloquentAssetRepository implements AssetRepository
{
    /**
     * Create a new instance of the AssetRepository class
     *
     * @param  Asset  $model  The Asset model
     */
    public function __construct(
        private Asset $model
    ) {}

    /**
     * Find asset by ID
     *
     * @param  int  $id  The asset ID
     * @return Asset|null The found asset or null if not found
     */
    public function findById(int $id): ?Asset
    {
        return $this->model->find($id);
    }

    /**
     * Find asset by user and symbol
     *
     * @param  User  $user  The user
     * @param  string  $symbol  The asset symbol
     * @return Asset|null The found asset or null if not found
     */
    public function findByUserAndSymbol(User $user, string $symbol): ?Asset
    {
        return $this->model
            ->where('user_id', $user->id)
            ->where('symbol', $symbol)
            ->first();
    }

    /**
     * Find asset by user and symbol with lock for update
     *
     * @param  User  $user  The user
     * @param  string  $symbol  The asset symbol
     * @return Asset|null The found asset or null if not found
     */
    public function findByUserAndSymbolForUpdate(User $user, string $symbol): ?Asset
    {
        return $this->model
            ->where('user_id', $user->id)
            ->where('symbol', $symbol)
            ->lockForUpdate()
            ->first();
    }

    /**
     * Get all assets for a user
     *
     * @param  User  $user  The user
     * @return Collection The collection of assets
     */
    public function getByUser(User $user): Collection
    {
        return $this->model
            ->where('user_id', $user->id)
            ->get();
    }

    /**
     * Create or update asset for user
     *
     * @param  User  $user  The user
     * @param  string  $symbol  The asset symbol
     * @param  array  $data  The asset data
     * @return Asset The created or updated asset
     */
    public function createOrUpdate(User $user, string $symbol, array $data): Asset
    {
        return $this->model->updateOrCreate(
            [
                'user_id' => $user->id,
                'symbol' => $symbol,
            ],
            $data
        );
    }

    /**
     * Update asset amounts
     *
     * @param  Asset  $asset  The asset
     * @param  string  $amount  The new available amount
     * @param  string  $lockedAmount  The new locked amount
     * @return bool True if update was successful, false otherwise
     */
    public function updateAmounts(Asset $asset, string $amount, string $lockedAmount): bool
    {
        return $asset->update([
            'amount' => $amount,
            'locked_amount' => $lockedAmount,
        ]);
    }

    /**
     * Lock amount for order (move from available to locked)
     *
     * @param  Asset  $asset  The asset
     * @param  string  $amount  The amount to lock
     * @return bool True if update was successful, false otherwise
     */
    public function lockAmount(Asset $asset, string $amount): bool
    {
        $newAmount = bcsub($asset->amount, $amount, 8);
        $newLocked = bcadd($asset->locked_amount, $amount, 8);

        if (bccomp($newAmount, '0', 8) < 0) {
            throw new \InvalidArgumentException('Insufficient asset amount');
        }

        return $this->updateAmounts($asset, $newAmount, $newLocked);
    }

    /**
     * Unlock amount from order (move from locked to available)
     *
     * @param  Asset  $asset  The asset
     * @param  string  $amount  The amount to unlock
     * @return bool True if update was successful, false otherwise
     */
    public function unlockAmount(Asset $asset, string $amount): bool
    {
        $newAmount = bcadd($asset->amount, $amount, 8);
        $newLocked = bcsub($asset->locked_amount, $amount, 8);

        if (bccomp($newLocked, '0', 8) < 0) {
            throw new \InvalidArgumentException('Invalid locked amount');
        }

        return $this->updateAmounts($asset, $newAmount, $newLocked);
    }

    /**
     * Decrement available amount
     *
     * @param  Asset  $asset  The asset
     * @param  string  $amount  The amount to decrement
     * @return bool True if update was successful, false otherwise
     */
    public function decrementAmount(Asset $asset, string $amount): bool
    {
        $newAmount = bcsub($asset->amount, $amount, 8);

        if (bccomp($newAmount, '0', 8) < 0) {
            throw new \InvalidArgumentException('Insufficient asset amount');
        }

        return $asset->update(['amount' => $newAmount]);
    }

    /**
     * Increment available amount
     *
     * @param  Asset  $asset  The asset
     * @param  string  $amount  The amount to increment
     * @return bool True if update was successful, false otherwise
     */
    public function incrementAmount(Asset $asset, string $amount): bool
    {
        $newAmount = bcadd($asset->amount, $amount, 8);

        return $asset->update(['amount' => $newAmount]);
    }

    /**
     * Decrement locked amount
     *
     * @param  Asset  $asset  The asset
     * @param  string  $amount  The amount to decrement
     * @return bool True if update was successful, false otherwise
     */
    public function decrementLockedAmount(Asset $asset, string $amount): bool
    {
        $newLocked = bcsub($asset->locked_amount, $amount, 8);

        if (bccomp($newLocked, '0', 8) < 0) {
            throw new \InvalidArgumentException('Invalid locked amount');
        }

        return $asset->update(['locked_amount' => $newLocked]);
    }
}
