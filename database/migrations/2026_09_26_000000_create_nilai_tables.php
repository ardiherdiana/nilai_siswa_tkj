<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Skema Nilai Siswa TKJ & Supervisi Guru. Primary key ULID string(191), kolom camelCase, createdAt/updatedAt.
 * Sesi dan cache memakai berkas, antrean sync, jadi tidak ada tabel framework.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->string('id', 191)->primary();
            $table->string('username', 191)->unique('users_username_key');
            $table->string('name', 100);
            $table->string('password');
            $table->string('role', 20)->default('GURU');
            $table->boolean('aktif')->default(true);
            $table->rememberToken();
            $table->dateTime('createdAt', 3)->useCurrent();
            $table->dateTime('updatedAt', 3);
        });

        Schema::create('kelas', function (Blueprint $table) {
            $table->string('id', 191)->primary();
            $table->string('userId', 191);
            $table->string('nama', 100);
            $table->string('tahunAjaran', 9);
            $table->dateTime('createdAt', 3)->useCurrent();
            $table->dateTime('updatedAt', 3);

            $table->unique(['userId', 'tahunAjaran', 'nama'], 'kelas_userId_tahunAjaran_nama_key');
            $table->foreign('userId', 'kelas_userId_fkey')
                ->references('id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::create('siswa', function (Blueprint $table) {
            $table->string('id', 191)->primary();
            $table->string('kelasId', 191);
            $table->unsignedSmallInteger('nomor');
            $table->string('nama', 150);
            $table->dateTime('createdAt', 3)->useCurrent();
            $table->dateTime('updatedAt', 3);

            $table->index(['kelasId', 'nomor'], 'siswa_kelasId_nomor_idx');
            $table->foreign('kelasId', 'siswa_kelasId_fkey')
                ->references('id')->on('kelas')->cascadeOnDelete()->cascadeOnUpdate();
        });

        // Satu baris per siswa; kolom mengikuti template (10 nilai tes teori + 10 nilai praktik).
        Schema::create('nilai', function (Blueprint $table) {
            $table->string('id', 191)->primary();
            $table->string('siswaId', 191)->unique('nilai_siswaId_key');
            foreach (['tes', 'praktik'] as $jenis) {
                for ($i = 1; $i <= 10; $i++) {
                    $table->decimal("{$jenis}{$i}", 5, 2)->nullable();
                }
            }
            $table->dateTime('createdAt', 3)->useCurrent();
            $table->dateTime('updatedAt', 3);

            $table->foreign('siswaId', 'nilai_siswaId_fkey')
                ->references('id')->on('siswa')->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::create('supervisi', function (Blueprint $table) {
            $table->string('id', 191)->primary();
            $table->string('userId', 191);
            $table->string('guruDinilai', 150);
            $table->string('pemberiUmpanBalik', 150);
            $table->date('tanggal');
            $table->json('butir');
            $table->json('refleksi');
            $table->dateTime('createdAt', 3)->useCurrent();
            $table->dateTime('updatedAt', 3);

            $table->index(['userId', 'tanggal'], 'supervisi_userId_tanggal_idx');
            $table->foreign('userId', 'supervisi_userId_fkey')
                ->references('id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supervisi');
        Schema::dropIfExists('nilai');
        Schema::dropIfExists('siswa');
        Schema::dropIfExists('kelas');
        Schema::dropIfExists('users');
    }
};
