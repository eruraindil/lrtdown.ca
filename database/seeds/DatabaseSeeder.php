<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // $this->call(UsersTableSeeder::class);
        // DB::table('tweets')->insert([
        //     'uid' => str_random(10),
        //     'text' => str_random(280),
        //     'created' => date_create(),
        // ]);
        factory(App\Tweet::class, 25)->create();
        // ->each(function ($u) {
        //     $u->posts()->save(factory(App\Post::class)->make());
        // });
    }
}
