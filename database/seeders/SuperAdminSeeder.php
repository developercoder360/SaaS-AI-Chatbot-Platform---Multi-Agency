<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create or retrieve System Tenant
        $systemTenant = Tenant::firstOrCreate(
            ['slug' => 'system'],
            [
                'name' => 'System Administration',
                'plan' => 'pro',
                'primary_color' => '#000000',
                'accent_color' => '#000000',
                'system_prompt' => 'System administrator mode.',
                'status' => 'active',
                'trial_ends_at' => null,
            ]
        );

        // 2. Create the Super Admin user
        $email = 'superadmin@okyai.io';

        $superAdmin = User::withoutGlobalScope('tenant')->where('email', $email)->first();

        if ($superAdmin) {
            $this->command->info("Super Admin '{$email}' already exists.");

            return;
        }

        User::create([
            'tenant_id' => $systemTenant->id,
            'name' => 'Super Administrator',
            'email' => $email,
            'password' => Hash::make('SuperSecretPassword123!'),
            'role' => 'super_admin',
        ]);

        $this->command->info('Super Admin created successfully!');
        $this->command->info("Email: {$email}");
        $this->command->info('Password: SuperSecretPassword123!');
    }
}
