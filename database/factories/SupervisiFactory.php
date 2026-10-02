<?php

namespace Database\Factories;

use App\Models\Supervisi;
use App\Models\User;
use App\Support\SupervisiInstrumen;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Supervisi> */
class SupervisiFactory extends Factory
{
    protected $model = Supervisi::class;

    public function definition(): array
    {
        return [
            'userId' => User::factory(),
            'guruDinilai' => fake()->name(),
            'pemberiUmpanBalik' => fake()->name(),
            'tanggal' => '2026-09-24',
            'butir' => SupervisiInstrumen::lengkapiButir([]),
            'refleksi' => SupervisiInstrumen::lengkapiRefleksi([]),
        ];
    }
}
