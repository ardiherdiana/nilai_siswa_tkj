<?php

namespace Database\Factories;

use App\Models\Kelas;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Kelas> */
class KelasFactory extends Factory
{
    protected $model = Kelas::class;

    public function definition(): array
    {
        return [
            'userId' => User::factory(),
            'nama' => 'X TKJ '.fake()->unique()->numberBetween(1, 999),
            'tahunAjaran' => '2026/2027',
        ];
    }
}
