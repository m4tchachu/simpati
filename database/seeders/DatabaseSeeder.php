<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed study programs first
        $this->call(StudyProgramSeeder::class);
        
        // Get first study program for mahasiswa
        $studyProgram = StudyProgram::first();
        $studyProgramId = $studyProgram ? $studyProgram->id : null;

        // Seed admin account
        User::factory()->create([
            'name' => 'Admin Simpati',
            'email' => 'admin@simpati.local',
            'password' => Hash::make('admin123456'),
            'role' => UserRole::ADMIN,
            'nim' => null,
            'study_program_id' => null,
        ]);

        // Seed mahasiswa accounts
        User::factory()->create([
            'name' => 'Budi Santoso',
            'email' => 'budi@mahasiswa.local',
            'password' => Hash::make('password123'),
            'role' => UserRole::MAHASISWA,
            'nim' => '2401001',
            'study_program_id' => $studyProgramId,
        ]);

        User::factory()->create([
            'name' => 'Siti Nurhaliza',
            'email' => 'siti@mahasiswa.local',
            'password' => Hash::make('password123'),
            'role' => UserRole::MAHASISWA,
            'nim' => '2401002',
            'study_program_id' => $studyProgramId,
        ]);

        User::factory()->create([
            'name' => 'Ahmad Wijaya',
            'email' => 'ahmad@mahasiswa.local',
            'password' => Hash::make('password123'),
            'role' => UserRole::MAHASISWA,
            'nim' => '2401003',
            'study_program_id' => $studyProgramId,
        ]);

        // Additional test user
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role' => UserRole::MAHASISWA,
            'study_program_id' => $studyProgramId,
        ]);
    }
}
