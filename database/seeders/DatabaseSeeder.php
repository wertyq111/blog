<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $tableNames = [
//            "users",
//            "web_info",
//            "im_chat_groups",
//            "im_chat_group_users",
//            "cities",
            //"level",
            //"member_level",
            //"menus",
            "positions",
            "roles",
            "role_menu",
            "user_role",
            "configs"
        ];
        foreach ($tableNames as $tableName) {
            $path = base_path() . '/database/seeders/sql/' . $tableName . '.sql';
            if (file_exists($path)) {
                DB::unprepared(file_get_contents($path));
                $this->command->info($tableName . ' table seeded!');
            }
        }
    }
}
