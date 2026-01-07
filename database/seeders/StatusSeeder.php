<?php

namespace Database\Seeders;

use App\Enums\Status;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (Status::cases() as $case) {
            DB::table('statuses')->upsert(
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
