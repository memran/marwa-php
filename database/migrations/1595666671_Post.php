<?php
	
	use Marwa\Application\Migrations\AbstractMigration;
	
	class Post extends AbstractMigration {
		
		public function up() : void
		{
			$this->table('post')
				->id()
				->bigInteger('user_id')
				->strings("post_name")
				->strings("post_slug")
				->strings("post_title")
				->longtext("post_content")
				->strings("post_excerpt")
				->strings("post_type")  // page/post/news
				->strings("post_status") //draft/public
				->tinyInteger("feature") //0 or 1
				->strings('feature_img')
				->strings("author_name")
				->integer('post_views') //post view counter
				->timestamps()
				->create();
		}
		
		public function down() : void
		{
			$this->table('post')->drop();
		}
	}
