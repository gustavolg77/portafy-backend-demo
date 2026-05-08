<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $countries = [
            "Argentina", "Bolivia", "Brasil", "Chile", "Colombia",
            "Ecuador", "México", "Paraguay", "Perú", "Uruguay",
            "Venezuela", "España", "Estados Unidos",
        ];

        foreach ($countries as $name) {
            DB::table('COUNTRY')->insert([
                'name'       => $name,
                'state'      => 'activate',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
