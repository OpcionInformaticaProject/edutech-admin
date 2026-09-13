<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Campus;
use App\Models\Municipality;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $organization = Organization::firstOrCreate(['name' => 'EDUTECH'], ['legal_name' => 'EDUTECH', 'active' => true]);
        $municipality = Municipality::firstOrCreate(['code' => '66682'], ['name' => 'Santa Rosa de Cabal', 'department' => 'Risaralda']);
        $campus = Campus::firstOrCreate(['organization_id' => $organization->id, 'code' => 'SRC'], ['municipality_id' => $municipality->id, 'name' => 'Santa Rosa', 'active' => true]);
        User::updateOrCreate(['email' => 'superadmin@edutech.test'], ['organization_id' => $organization->id, 'campus_id' => $campus->id, 'name' => 'Super Administrador', 'role' => UserRole::Superadmin, 'active' => true, 'password' => 'Edutech2026!']);
    }
}
