<?php


	use Marwa\Application\Facades\DB;
	use Marwa\Application\Migrations\AbstractSeeder;
	use Marwa\Application\Security\Bcrypt;

	class UserSeeder extends AbstractSeeder {

		/**
		 *
		 */
		public function run() : void
		{
			DB::table('role')->insert([
				                          ['name' => 'Admin'],
				                          ['name' => 'User'],
				                          ['name' => 'Broker']
			                          ]);

			$passwords = new Bcrypt();
			$user = [
				'name' => 'Mohammad Emran',
				'username' => 'admin',
				'password' => $passwords->create("admin123"),
				'email' => 'memran.dhk@gmail.com',
				'active' => 1,
				'remember_token' => generate(32)
			];
			$id = DB::table('user')->insertGetId($user);

			DB::table('user_has_role')->insert(['user_id' => $id, 'role_id' => 1]);
			DB::table('privacy')->insert(['user_id' => $id]);
			DB::table('preference')->insert(['user_id' => $id]);
			DB::table('profile')->insert(['user_id' => $id]);

			DB::table('permission')->insert([
				                                ['name' => "add  user", 'guard_name' => 'add.user'],
				                                ['name' => "edit user", 'guard_name' => 'edit.user'],
				                                ['name' => "delete user", 'guard_name' => 'delete.user'],
				                                ['name' => "view user", 'guard_name' => 'view.user'],
				                                ['name' => "add role", 'guard_name' => 'add.role'],
				                                ['name' => "edit role", 'guard_name' => 'edit.role'],
				                                ['name' => "delete role", 'guard_name' => 'delete.role'],
				                                ['name' => "view role", 'guard_name' => 'view.role'],
				                                ['name' => "add permission", 'guard_name' => 'add.permission'],
				                                ['name' => "edit permission", 'guard_name' => 'edit.permission'],
				                                ['name' => "delete permission", 'guard_name' => 'delete.permission'],
				                                ['name' => "view permission", 'guard_name' => 'view.permission'],
			                                ]);
			DB::table('role_has_permission')->insert([
				                                         ['role_id' => 1, 'permission_id' => 1],
				                                         ['role_id' => 1, 'permission_id' => 2],
				                                         ['role_id' => 1, 'permission_id' => 3],
				                                         ['role_id' => 1, 'permission_id' => 4],
				                                         ['role_id' => 1, 'permission_id' => 5],
				                                         ['role_id' => 1, 'permission_id' => 6],
				                                         ['role_id' => 1, 'permission_id' => 7],
				                                         ['role_id' => 1, 'permission_id' => 8],
				                                         ['role_id' => 1, 'permission_id' => 9],
				                                         ['role_id' => 1, 'permission_id' => 10],
				                                         ['role_id' => 1, 'permission_id' => 11],
				                                         ['role_id' => 1, 'permission_id' => 12],
			                                         ]);

		}
	}
