<?php

namespace Database\Seeders;

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
        $this->call(RolePermissionSeeder::class);

        $password = Hash::make('password');

        $users = [
            [
                'name' => 'Admin',
                'email' => 'admin@gmail.com',
                'role' => 'admin',
            ],
            [
                'name' => 'Professor',
                'email' => 'professor@gmail.com',
                'role' => 'professor',
            ],
            [
                'name' => 'User',
                'email' => 'user@gmail.com',
                'role' => 'user',
            ],
        ];

        foreach ($users as $user) {
            $createdUser = User::query()->updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'email_verified_at' => now(),
                    'password' => $password,
                ],
            );

            $createdUser->syncRoles([$user['role']]);
        }

        $this->call(FacultySeeder::class);
        $this->call(ProgramSeeder::class);
        $this->call(ScienceTechnologyCourseSeeder::class);

        // Disabled temporarily:
        // $this->call(BusinessAdministrationCourseSeeder::class);
    }
}
