<?php

namespace Database\Seeders;

use App\Enums\Side;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SideSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (Side::cases() as $case) {
            DB::table('sides')->upsert(
                [
                    'id' => $case->value,
                    'name' => $case->name,
                ],
                ['id'],
                ['name']
            );
        }
    }
}
