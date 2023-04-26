<?php
	
	use Marwa\Application\Facades\DB;
	use Marwa\Application\Migrations\AbstractSeeder;
	use Marwa\Application\Utils\Str;
	
	class PostSeeder extends AbstractSeeder {
		
		/**
		 *
		 */
		public function run() : void
		{
			
			$faker = Faker\Factory::create();
			$posts = [];
			for ( $i = 1; $i < 100; $i++ )
			{
				$post = [
					'user_id' => 1,
					'post_name' => $faker->text(10),
					'post_title' => $faker->sentence(),
					'post_slug' => Str::webalize($faker->text(50)),
					'post_content' => $faker->text(300),
					'post_excerpt' => Str::truncate($faker->text(100), 20),
					'post_status' => $faker->randomElement(['draft', 'publish']),
					'post_type' => $faker->randomElement(['page', 'post', 'news']),
					'post_views' => $faker->randomNumber(3),
					'feature' => $faker->numberBetween(1, 0),
					'feature_img' => $faker->imageUrl($width = 640, $height = 480),
					'author_name' => $faker->name()
				];
				array_push($posts, $post);
			}
			
			//DB::enableQueryLog();
			DB::table('post')->insert($posts);
			//var_dump(DB::getQueryLog());
			
		}
	}
