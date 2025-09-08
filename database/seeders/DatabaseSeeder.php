<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\UserProfile;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Run the roles & permissions first
        $this->call(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create([
            'name' => 'Admin',
            'email' => 'shadow902@gmail.com',
            'password' => Hash::make('Welc0me!1225'),
        ]);

        $user_profile = UserProfile::create([
            'user_id' => $user->id,
            'display_name' => 'Site Administrator',
        ]);

        $user->assignRole('admin');

        $user = User::factory()->create([
            'name' => 'Test User 1',
            'email' => 'testuser1@example.com',
            'password' => Hash::make('Welc0me!1225'),
        ]);

        $user_profile = UserProfile::create([
            'user_id' => $user->id,
            'display_name' => 'Test User 1',
        ]);

        $user->assignRole('subscriber');

        $user = User::factory()->create([
            'name' => 'Test User 2',
            'email' => 'testuser2@example.com',
            'password' => Hash::make('Welc0me!1225'),
        ]);

        $user_profile = UserProfile::create([
            'user_id' => $user->id,
            'display_name' => 'Test User 2',
        ]);

        $user->assignRole('subscriber');

        $user = User::factory()->create([
            'name' => 'Test User 3',
            'email' => 'testuser3@example.com',
            'password' => Hash::make('Welc0me!1225'),
        ]);

        $user_profile = UserProfile::create([
            'user_id' => $user->id,
            'display_name' => 'Test User 3',
        ]);

        $user->assignRole('subscriber');        

    }
}