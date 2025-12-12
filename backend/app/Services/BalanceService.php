<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepository;
use InvalidArgumentException;

class BalanceService
{
    /**
     * Constructor
     *
     * @param  UserRepository  $userRepository  The user repository
     */
    public function __construct(
        private UserRepository $userRepository
    ) {}

    /**
     * Add balance to user
     *
     * @param  User  $user  The user
     * @param  string  $amount  The amount to add
     */
    public function add(User $user, string $amount): void
    {
        if (bccomp($amount, '0', 8) < 0) {
            throw new InvalidArgumentException('Amount must be positive');
        }

        $this->userRepository->incrementBalance($user, $amount);
        $user->refresh();
    }

    /**
     * Deduct balance from user
     *
     * @param  User  $user  The user
     * @param  string  $amount  The amount to deduct
     */
    public function deduct(User $user, string $amount): void
    {
        if (bccomp($amount, '0', 8) < 0) {
            throw new InvalidArgumentException('Amount must be positive');
        }

        $this->userRepository->decrementBalance($user, $amount);
        $user->refresh();
    }

    /**
     * Check if user has sufficient balance
     *
     * @param  User  $user  The user
     * @param  string  $required  The required amount
     * @return bool True if sufficient, false otherwise
     */
    public function hasSufficientBalance(User $user, string $required): bool
    {
        return bccomp($user->balance, $required, 8) >= 0;
    }

    /**
     * Lock balance for order (atomic: deduct from balance)
     * For buy orders, we deduct from balance when order is placed
     *
     * @param  User  $user  The user
     * @param  string  $amount  The amount to lock
     */
    public function lockForOrder(User $user, string $amount): void
    {
        $this->deduct($user, $amount);
    }

    /**
     * Release locked balance (return to balance)
     * For cancelled buy orders, we add back to balance
     *
     * @param  User  $user  The user
     * @param  string  $amount  The amount to release
     */
    public function releaseForOrder(User $user, string $amount): void
    {
        $this->add($user, $amount);
    }

    /**
     * Get user with lock for update (used in transactions)
     *
     * @param  int  $userId  The user ID
     * @return User|null The found user or null if not found
     */
    public function getUserForUpdate(int $userId): ?User
    {
        return $this->userRepository->findByIdForUpdate($userId);
    }

    /**
     * Credit balance after trade
     *
     * @param  User  $user  The user
     * @param  string  $amount  The amount to credit
     */
    public function credit(User $user, string $amount): void
    {
        $this->add($user, $amount);
    }
}
