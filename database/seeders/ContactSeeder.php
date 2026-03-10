<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
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
        $tags = Tag::all();

        // サンプルデータを20件作成
        for ($i = 0; $i < 20; $i++) {
            $contact = Contact::create([
                'first_name' => $faker->lastName,
                'last_name' => $faker->firstName,
                'gender' => $faker->numberBetween(1, 3),
                'email' => $faker->unique()->safeEmail,
                'tel' => $faker->numerify('###########'),
                'address' => $faker->prefecture.$faker->city.$faker->streetAddress,
                'building' => $faker->optional()->secondaryAddress,
                'category_id' => $categories->random()->id,
                'detail' => $faker->realText(120),
            ]);

            // タグをランダムに1〜3件紐付け
            if ($tags->isNotEmpty()) {
                $randomTags = $tags->random(rand(1, min(3, $tags->count())));
                $contact->tags()->attach($randomTags->pluck('id'));
            }
        }
    }
}
