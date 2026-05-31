<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Admin', 'code' => 'admin', 'description' => 'Full system access', 'is_active' => true],
            ['name' => 'Receptionist', 'code' => 'receptionist', 'description' => 'Front desk operations', 'is_active' => true],
            ['name' => 'Manager', 'code' => 'manager', 'description' => 'Management and reports', 'is_active' => true],
        ];
        foreach ($roles as $r) {
            Role::updateOrCreate(['code' => $r['code']], $r);
        }
        // Sync existing users: set role_id from role string
        User::whereNull('role_id')->get()->each(function (User $u) {
            $rawRole = $u->getRawOriginal('role') ?? 'receptionist';
            $roleId = Role::where('code', $rawRole)->value('id');
            if ($roleId) {
                $u->update(['role_id' => $roleId]);
            }
        });
    }
}
