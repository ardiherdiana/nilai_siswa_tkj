<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Halaman dirender lewat app.blade.php yang memanggil @vite; tes tidak butuh hasil build.
        $this->withoutVite();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /** @param  array<string, mixed>  $atribut */
    protected function actingAsAdmin(array $atribut = []): User
    {
        $user = User::factory()->admin()->create($atribut);
        $this->actingAs($user);

        return $user;
    }

    /** @param  array<string, mixed>  $atribut */
    protected function actingAsGuru(array $atribut = []): User
    {
        $user = User::factory()->create($atribut);
        $this->actingAs($user);

        return $user;
    }

    /** Props halaman Inertia dari sebuah respons, untuk memeriksa daftar (AssertableInertia tidak mengenal wildcard). */
    protected function inertiaProps(TestResponse $respons): array
    {
        return AssertableInertia::fromTestResponse($respons)->toArray()['props'];
    }

    protected function templateNilaiAsli(): string
    {
        return config('aplikasi.templates.nilai');
    }
}
