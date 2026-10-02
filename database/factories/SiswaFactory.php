<?php

namespace Database\Factories;

use App\Models\Kelas;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Siswa> */
class SiswaFactory extends Factory
{
    protected $model = Siswa::class;

    public function definition(): array
    {
        return [
            'kelasId' => Kelas::factory(),
            'nomor' => fake()->numberBetween(1, 60000),
            'nama' => fake()->name(),
        ];
    }
}
