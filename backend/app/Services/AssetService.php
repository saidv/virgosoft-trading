<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\User;
use App\Repositories\Contracts\AssetRepository;
use InvalidArgumentException;

class AssetService
{
    /**
     * Constructor
     *
     * @param  AssetRepository  $assetRepository  The asset repository
     */
    public function __construct(
        private AssetRepository $assetRepository
    ) {}

    /**
     * Add asset to user
     *
     * @param  User  $user  The user
     * @param  string  $symbol  The asset symbol
     * @param  string  $amount  The amount to add
     * @return Asset The updated or created asset
     */
    public function add(User $user, string $symbol, string $amount): Asset
    {
        $this->validatePositiveAmount($amount);

        $asset = $this->assetRepository->findByUserAndSymbol($user, $symbol);

        if ($asset) {
            $this->assetRepository->incrementAmount($asset, $amount);
            $asset->refresh();
        } else {
            $asset = $this->assetRepository->createOrUpdate($user, $symbol, [
                'amount' => $amount,
                'locked_amount' => '0.00000000',
            ]);
        }

        return $asset;
    }

    /**
     * Lock asset for sell order (move from amount to locked_amount)
     *
     * @param  User  $user  The user
     * @param  string  $symbol  The asset symbol
     * @param  string  $amount  The amount to lock
     * @return Asset The updated asset
     */
    public function lock(User $user, string $symbol, string $amount): Asset
    {
        $this->validatePositiveAmount($amount);

        $asset = $this->assetRepository->findByUserAndSymbolForUpdate($user, $symbol);

        if (! $asset) {
            throw new InvalidArgumentException("No {$symbol} asset found");
        }

        $available = $asset->getAvailableAmount();

        if (bccomp($available, $amount, 8) < 0) {
            throw new InvalidArgumentException("Insufficient {$symbol}. Required: {$amount}, Available: {$available}");
        }

        $this->assetRepository->lockAmount($asset, $amount);
        $asset->refresh();

        return $asset;
    }

    /**
     * Unlock asset (move from locked_amount back to available)
     * Used when sell order is cancelled
     *
     * @param  User  $user  The user
     * @param  string  $symbol  The asset symbol
     * @param  string  $amount  The amount to unlock
     * @return Asset The updated asset
     */
    public function unlock(User $user, string $symbol, string $amount): Asset
    {
        $this->validatePositiveAmount($amount);

        $asset = $this->assetRepository->findByUserAndSymbolForUpdate($user, $symbol);

        if (! $asset) {
            throw new InvalidArgumentException("No {$symbol} asset found");
        }

        if (bccomp($asset->locked_amount, $amount, 8) < 0) {
            throw new InvalidArgumentException('Cannot unlock more than locked amount');
        }

        $this->assetRepository->unlockAmount($asset, $amount);
        $asset->refresh();

        return $asset;
    }

    /**
     * Transfer locked asset from seller to buyer
     * Used when trade is executed
     *
     * @param  User  $seller  The seller user
     * @param  User  $buyer  The buyer user
     * @param  string  $symbol  The asset symbol
     * @param  string  $amount  The amount to transfer
     */
    public function transferLocked(User $seller, User $buyer, string $symbol, string $amount): void
    {
        $this->validatePositiveAmount($amount);

        $this->deductFromSellerLocked($seller, $symbol, $amount);
        $this->addToBuyer($buyer, $symbol, $amount);
    }

    /**
     * Deduct amount from seller's locked assets
     */
    private function deductFromSellerLocked(User $seller, string $symbol, string $amount): void
    {
        $sellerAsset = $this->assetRepository->findByUserAndSymbolForUpdate($seller, $symbol);

        if (! $sellerAsset) {
            throw new InvalidArgumentException("Seller has no {$symbol} asset");
        }

        if (bccomp($sellerAsset->locked_amount, $amount, 8) < 0) {
            throw new InvalidArgumentException("Seller has insufficient locked {$symbol}");
        }

        // Only deduct from seller's locked amount (amount was already reduced during lock)
        $newLocked = bcsub($sellerAsset->locked_amount, $amount, 8);
        $this->assetRepository->updateAmounts($sellerAsset, $sellerAsset->amount, $newLocked);
    }

    /**
     * Add amount to buyer's assets
     */
    private function addToBuyer(User $buyer, string $symbol, string $amount): void
    {
        $buyerAsset = $this->assetRepository->findByUserAndSymbol($buyer, $symbol);

        if ($buyerAsset) {
            $this->assetRepository->incrementAmount($buyerAsset, $amount);
        } else {
            $this->assetRepository->createOrUpdate($buyer, $symbol, [
                'amount' => $amount,
                'locked_amount' => '0.00000000',
            ]);
        }
    }

    /**
     * Validate that amount is positive
     */
    private function validatePositiveAmount(string $amount): void
    {
        if (bccomp($amount, '0', 8) < 0) {
            throw new InvalidArgumentException('Amount must be positive');
        }
    }

    /**
     * Check if user has sufficient available asset
     *
     * @param  User  $user  The user
     * @param  string  $symbol  The asset symbol
     * @param  string  $required  The required amount
     * @return bool True if sufficient, false otherwise
     */
    public function hasSufficientAsset(User $user, string $symbol, string $required): bool
    {
        $asset = $this->assetRepository->findByUserAndSymbol($user, $symbol);

        if (! $asset) {
            return false;
        }

        return bccomp($asset->getAvailableAmount(), $required, 8) >= 0;
    }

    /**
     * Get or create asset for user
     *
     * @param  User  $user  The user
     * @param  string  $symbol  The asset symbol
     * @return Asset The found or created asset
     */
    public function getOrCreate(User $user, string $symbol): Asset
    {
        return $this->assetRepository->createOrUpdate($user, $symbol, [
            'amount' => '0.00000000',
            'locked_amount' => '0.00000000',
        ]);
    }
}
