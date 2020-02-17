<?php

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

       $permissions = [
           'List Articles',
           'Add Article',
           'Edit Article',
           'Delete Article',
           'super'
        ];

        foreach ($permissions as $permission) {
             Permission::create(['guard_name' => 'admin','name' => $permission]);
        }

        $role = Role::create(['guard_name' => 'admin','name' => 'Writer'])
            ->givePermissionTo(['List Articles','Add Article', 'Edit Article','Delete Article']);

        $role = Role::create(['guard_name' => 'admin','name' => 'SuperAdmin'])
            ->givePermissionTo(['super']);

    }
}
