<?php

namespace Database\Seeders;

use App\Enums\roleEnum;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // $roles = require(base_path('data/roles.php'));
        $roles =roleEnum::cases();
        foreach ($roles as $role) {
          Role::factory()->create(['label'=>$role]);
        }
    }
}
