<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = [
            [
                'name' => 'Main Office - Head Office',
                'address' => '604 SBSI Building, Ermita, Manila, Metro Manila',
            ],
            [
                'name' => 'Makati Commercial Branch',
                'address' => 'Ayala Triangle, Makati City, Metro Manila',
            ],
            [
                'name' => 'Cebu Regional Hub',
                'address' => 'Cebu Business Park, Cebu City, Cebu',
            ],
            [
                'name' => 'Davao Operations Branch',
                'address' => 'Bajada Commercial Center, Davao City, Davao del Sur',
            ],
        ];

        foreach ($branches as $branchData) {
            Branch::firstOrCreate(['name' => $branchData['name']], $branchData);
        }

        $mainBranch = Branch::where('name', 'Main Office - Head Office')->first();
        if ($mainBranch) {
            UserProfile::whereNull('branch_id')->update(['branch_id' => $mainBranch->id]);
        }
    }
}
