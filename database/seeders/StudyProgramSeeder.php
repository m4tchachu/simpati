<?php

namespace Database\Seeders;

use App\Models\StudyProgram;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StudyProgramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $programs = [
            [
                'code' => 'TIF',
                'name' => 'Teknik Informatika',
                'faculty' => 'Fakultas Teknik',
            ],
            [
                'code' => 'SI',
                'name' => 'Sistem Informasi',
                'faculty' => 'Fakultas Teknik',
            ],
            [
                'code' => 'TK',
                'name' => 'Teknik Komputer',
                'faculty' => 'Fakultas Teknik',
            ],
            [
                'code' => 'TE',
                'name' => 'Teknik Elektro',
                'faculty' => 'Fakultas Teknik',
            ],
            [
                'code' => 'AK',
                'name' => 'Akuntansi',
                'faculty' => 'Fakultas Ekonomi',
            ],
            [
                'code' => 'MJ',
                'name' => 'Manajemen',
                'faculty' => 'Fakultas Ekonomi',
            ],
        ];

        foreach ($programs as $program) {
            StudyProgram::firstOrCreate(
                ['code' => $program['code']],
                [
                    'name' => $program['name'],
                    'faculty' => $program['faculty'],
                ]
            );
        }
    }
}
