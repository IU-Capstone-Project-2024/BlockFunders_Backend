<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RolesPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = Permission::all()->pluck('name')->toArray();

        if (!in_array('users.read', $permissions))
            Permission::create(['name' => 'users.read']);
        if (!in_array('users.write', $permissions))
            Permission::create(['name' => 'users.write']);
        if (!in_array('users.delete', $permissions))
            Permission::create(['name' => 'users.delete']);
        if (!in_array('roles.read', $permissions))
            Permission::create(['name' => 'roles.read']);
        if (!in_array('roles.write', $permissions))
            Permission::create(['name' => 'roles.write']);
        if (!in_array('roles.delete', $permissions))
            Permission::create(['name' => 'roles.delete']);
        if (!in_array('campaigns.read', $permissions))
            Permission::create(['name' => 'campaigns.read']);
        if (!in_array('campaigns.write', $permissions))
            Permission::create(['name' => 'campaigns.write']);
        if (!in_array('campaigns.delete', $permissions))
            Permission::create(['name' => 'campaigns.delete']);
        if (!in_array('campaign_categories.read', $permissions))
            Permission::create(['name' => 'campaign_categories.read']);
        if (!in_array('campaign_categories.write', $permissions))
            Permission::create(['name' => 'campaign_categories.write']);
        if (!in_array('campaign_categories.delete', $permissions))
            Permission::create(['name' => 'campaign_categories.delete']);

        if (!Role::where('name', 'admin')->exists())
            Role::create([
                'name' => 'admin',
                'guard_name' => 'web',
            ]);

        if (!Role::where('name', 'user')->exists())
            Role::create([
                'name' => 'user',
                'guard_name' => 'web',
            ]);

        $adminRole = Role::where('name', 'admin')->first();
        $adminPermissions = Permission::pluck('id')->toArray();
        $adminRole->syncPermissions($adminPermissions);
    }
}
