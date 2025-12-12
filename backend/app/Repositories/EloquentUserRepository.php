<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\UserRepository;

class EloquentUserRepository implements UserRepository
{
    /**
     * Constructor
     *
     * @param  User  $model  The User model
     */
    public function __construct(
        private User $model
    ) {}

    /**
     * Find user by ID
     *
     * @param  int  $id  The user ID
     * @return User|null The found user or null if not found
     */
    public function findById(int $id): ?User
    {
        return $this->model->find($id);
    }

    /**
     * Find user by ID with lock for update
     *
     * @param  int  $id  The user ID
     * @return User|null The found user or null if not found
     */
    public function findByIdForUpdate(int $id): ?User
    {
        return $this->model->lockForUpdate()->find($id);
    }

    /**
     * Find user by email
     *
     * @param  string  $email  The user email
     * @return User|null The found user or null if not found
     */
    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Get user with assets
     *
     * @param  int  $id  The user ID
     * @return User|null The found user with assets or null if not found
     */
    public function findWithAssets(int $id): ?User
    {
        return $this->model->with('assets')->find($id);
    }

    /**
     * Update user balance
     *
     * @param  User  $user  The user
     * @param  string  $amount  The new balance amount
     * @return bool True if update was successful, false otherwise
     */
    public function updateBalance(User $user, string $amount): bool
    {
        return $user->update(['balance' => $amount]);
    }

    /**
     * Increment user balance
     *
     * @param  User  $user  The user
     * @param  string  $amount  The amount to increment
     * @return bool True if update was successful, false otherwise
     */
    public function incrementBalance(User $user, string $amount): bool
    {
        $newBalance = bcadd($user->balance, $amount, 8);

        return $this->updateBalance($user, $newBalance);
    }

    /**
     * Decrement user balance
     *
     * @param  User  $user  The user
     * @param  string  $amount  The amount to decrement
     * @return bool True if update was successful, false otherwise
     */
    public function decrementBalance(User $user, string $amount): bool
    {
        $newBalance = bcsub($user->balance, $amount, 8);

        if (bccomp($newBalance, '0', 8) < 0) {
            throw new \InvalidArgumentException('Insufficient balance');
        }

        return $this->updateBalance($user, $newBalance);
    }

    /**
     * Create a new user
     *
     * @param  array  $data  The user data
     * @return User The created user
     */
    public function create(array $data): User
    {
        return $this->model->create($data);
    }

    /**
     * Update user
     *
     * @param  User  $user  The user
     * @param  array  $data  The user data to update
     * @return bool True if update was successful, false otherwise
     */
    public function update(User $user, array $data): bool
    {
        return $user->update($data);
    }
}
