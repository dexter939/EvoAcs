<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('role_permission')->truncate();
        DB::table('user_role')->truncate();
        Permission::truncate();
        Role::truncate();

        $permissions = [
            ['name' => 'View Devices', 'slug' => 'devices.view', 'module' => 'devices', 'action' => 'view', 'description' => 'View CPE devices list and details'],
            ['name' => 'Create Devices', 'slug' => 'devices.create', 'module' => 'devices', 'action' => 'create', 'description' => 'Add new CPE devices'],
            ['name' => 'Edit Devices', 'slug' => 'devices.edit', 'module' => 'devices', 'action' => 'edit', 'description' => 'Modify CPE device settings'],
            ['name' => 'Delete Devices', 'slug' => 'devices.delete', 'module' => 'devices', 'action' => 'delete', 'description' => 'Remove CPE devices'],
            ['name' => 'Reboot Devices', 'slug' => 'devices.reboot', 'module' => 'devices', 'action' => 'reboot', 'description' => 'Reboot CPE devices remotely'],
            
            ['name' => 'View Provisioning', 'slug' => 'provisioning.view', 'module' => 'provisioning', 'action' => 'view', 'description' => 'View provisioning tasks and profiles'],
            ['name' => 'Create Provisioning', 'slug' => 'provisioning.create', 'module' => 'provisioning', 'action' => 'create', 'description' => 'Create provisioning tasks'],
            ['name' => 'Execute Provisioning', 'slug' => 'provisioning.execute', 'module' => 'provisioning', 'action' => 'execute', 'description' => 'Execute provisioning on devices'],
            
            ['name' => 'View Firmware', 'slug' => 'firmware.view', 'module' => 'firmware', 'action' => 'view', 'description' => 'View firmware versions'],
            ['name' => 'Upload Firmware', 'slug' => 'firmware.upload', 'module' => 'firmware', 'action' => 'upload', 'description' => 'Upload firmware files'],
            ['name' => 'Deploy Firmware', 'slug' => 'firmware.deploy', 'module' => 'firmware', 'action' => 'deploy', 'description' => 'Deploy firmware to devices'],
            
            ['name' => 'View Diagnostics', 'slug' => 'diagnostics.view', 'module' => 'diagnostics', 'action' => 'view', 'description' => 'View diagnostic results'],
            ['name' => 'Run Diagnostics', 'slug' => 'diagnostics.run', 'module' => 'diagnostics', 'action' => 'run', 'description' => 'Execute diagnostic tests'],
            
            ['name' => 'View Alarms', 'slug' => 'alarms.view', 'module' => 'alarms', 'action' => 'view', 'description' => 'View system alarms'],
            ['name' => 'Manage Alarms', 'slug' => 'alarms.manage', 'module' => 'alarms', 'action' => 'manage', 'description' => 'Acknowledge and clear alarms'],
            
            ['name' => 'View Security', 'slug' => 'security.view', 'module' => 'security', 'action' => 'view', 'description' => 'View security dashboard and logs'],
            ['name' => 'Manage Security', 'slug' => 'security.manage', 'module' => 'security', 'action' => 'manage', 'description' => 'Block/unblock IPs and manage security'],
            
            ['name' => 'View Monitoring', 'slug' => 'monitoring.view', 'module' => 'monitoring', 'action' => 'view', 'description' => 'View performance monitoring'],
            ['name' => 'Manage Monitoring', 'slug' => 'monitoring.manage', 'module' => 'monitoring', 'action' => 'manage', 'description' => 'Configure monitoring and alerts'],
            
            ['name' => 'View Users', 'slug' => 'users.view', 'module' => 'users', 'action' => 'view', 'description' => 'View users list'],
            ['name' => 'Manage Users', 'slug' => 'users.manage', 'module' => 'users', 'action' => 'manage', 'description' => 'Create, edit, delete users'],
            ['name' => 'Manage Roles', 'slug' => 'roles.manage', 'module' => 'roles', 'action' => 'manage', 'description' => 'Manage roles and permissions'],
            
            ['name' => 'View Data Models', 'slug' => 'datamodels.view', 'module' => 'datamodels', 'action' => 'view', 'description' => 'View TR-069 data models'],
            ['name' => 'Import Data Models', 'slug' => 'datamodels.import', 'module' => 'datamodels', 'action' => 'import', 'description' => 'Import vendor data models'],
            
            ['name' => 'System Admin', 'slug' => 'system.admin', 'module' => 'system', 'action' => 'admin', 'description' => 'Full system administration access', 'is_system' => true],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }

        $roles = [
            [
                'name' => 'Super Administrator',
                'slug' => 'super-admin',
                'description' => 'Full system access - all permissions',
                'level' => 100,
                'is_system' => true,
                'permissions' => Permission::all()->pluck('id')->toArray(),
            ],
            [
                'name' => 'Administrator',
                'slug' => 'admin',
                'description' => 'Administrative access - most permissions',
                'level' => 80,
                'is_system' => true,
                'permissions' => Permission::whereNotIn('slug', ['system.admin', 'roles.manage'])->pluck('id')->toArray(),
            ],
            [
                'name' => 'Operator',
                'slug' => 'operator',
                'description' => 'Operational access - device management and provisioning',
                'level' => 50,
                'is_system' => false,
                'permissions' => Permission::whereIn('module', ['devices', 'provisioning', 'diagnostics', 'alarms'])->pluck('id')->toArray(),
            ],
            [
                'name' => 'Technician',
                'slug' => 'technician',
                'description' => 'Technical support - diagnostics and monitoring',
                'level' => 30,
                'is_system' => false,
                'permissions' => Permission::whereIn('module', ['devices', 'diagnostics', 'monitoring'])
                    ->whereIn('action', ['view', 'run'])
                    ->pluck('id')->toArray(),
            ],
            [
                'name' => 'Viewer',
                'slug' => 'viewer',
                'description' => 'Read-only access - view only',
                'level' => 10,
                'is_system' => false,
                'permissions' => Permission::where('action', 'view')->pluck('id')->toArray(),
            ],
        ];

        foreach ($roles as $roleData) {
            $permissions = $roleData['permissions'];
            unset($roleData['permissions']);
            
            $role = Role::create($roleData);
            $role->permissions()->attach($permissions);
        }

        $this->command->info('Roles and Permissions seeded successfully!');
        $this->command->info('Created ' . Permission::count() . ' permissions');
        $this->command->info('Created ' . Role::count() . ' roles');
    }
}
