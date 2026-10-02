<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => User::GURU,
            'aktif' => true,
        ];
    }

    public function admin(): static
    {
        return $this->state(['role' => User::ADMIN]);
    }

    public function nonaktif(): static
    {
        return $this->state(['aktif' => false]);
    }
}
