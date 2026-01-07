<?php

namespace Database\Seeders;

use App\Enums\Symbol;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SymbolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (Symbol::cases() as $case) {
            DB::table('symbols')->upsert(
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
