<?php

namespace App\Repositories\Contracts;

use App\Models\User;

interface UserRepository
{
    /**
     * Find user by ID
     */
    public function findById(int $id): ?User;

    /**
     * Find user by ID with lock for update
     */
    public function findByIdForUpdate(int $id): ?User;

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?User;

    /**
     * Get user with assets
     */
    public function findWithAssets(int $id): ?User;

    /**
     * Update user balance
     */
    public function updateBalance(User $user, string $amount): bool;

    /**
     * Increment user balance
     */
    public function incrementBalance(User $user, string $amount): bool;

    /**
     * Decrement user balance
     */
    public function decrementBalance(User $user, string $amount): bool;

    /**
     * Create a new user
     */
    public function create(array $data): User;

    /**
     * Update user
     */
    public function update(User $user, array $data): bool;
}
