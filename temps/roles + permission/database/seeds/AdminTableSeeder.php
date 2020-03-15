<?php

use Illuminate\Database\Seeder;
use App\Models\Admin;
use Faker\Factory as Faker;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class AdminTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // factory(App\Models\Admin::class, 5)->create();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $faker = Faker::create();

        $admin = Admin::create([
                    'name' => $faker->name,
                    // 'email' => $faker->unique()->safeEmail,
                    'email' => 'admin@gmail.com',
                    'email_verified_at' => now(),
                    'password' => '123456', // password
                    'remember_token' => Str::random(10)
            ]);

        $admin->assignRole('SuperAdmin');

        $admin2 = Admin::create([
                    'name' => $faker->name,
                    'email' => 'admin2@gmail.com',
                    'email_verified_at' => now(),
                    'password' => '123456', // password
                    'remember_token' => Str::random(10)
            ]);

        $admin2->assignRole('Writer');

        Admin::create([
                'name' => $faker->name,
                'email' => 'admin3@gmail.com',
                'email_verified_at' => now(),
                'password' => '123456', // password
                'remember_token' => Str::random(10)
        ]);

    }
}
