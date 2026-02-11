<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Contact;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('ja_JP');
        $categories = Category::all();

        // サンプルデータを20件作成
        for ($i = 0; $i < 20; $i++) {
            Contact::create([
                'first_name' => $faker->firstName,
                'last_name' => $faker->lastName,
                'gender' => $faker->numberBetween(1, 3),
                'email' => $faker->unique()->safeEmail,
                'tel' => $faker->numerify('###########'),
                'address' => $faker->prefecture.$faker->city.$faker->streetAddress,
                'building' => $faker->optional()->secondaryAddress,
                'category_id' => $categories->random()->id,
                'detail' => $faker->realText(120),
            ]);
        }
    }
}
