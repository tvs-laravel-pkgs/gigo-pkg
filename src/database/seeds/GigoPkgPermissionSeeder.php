<?php
namespace Abs\GigoPkg\Database\Seeds;

use App\Permission;
use Illuminate\Database\Seeder;

class GigoPkgPermissionSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		$permissions = [
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'mobile-simulation',
				'display_name' => 'Mobile Simulation',
			],

			//Job Cards
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'job-cards',
				'display_name' => 'Job Cards',
			],
			[
				'display_order' => 1,
				'parent' => 'job-cards',
				'name' => 'add-job-card',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'job-cards',
				'name' => 'edit-job-card',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'job-cards',
				'name' => 'view-job-card',
				'display_name' => 'View',
			],
			[
				'display_order' => 4,
				'parent' => 'job-cards',
				'name' => 'delete-job-card',
				'display_name' => 'Delete',
			],

			//KANBAN App
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'kanban-app',
				'display_name' => 'KANBAN App',
			],

			//Mobile Permissions
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'mobile-permissions',
				'display_name' => 'Mobile Permissions',
			],

			//My Job Cards
			[
				'display_order' => 99,
				'parent' => 'mobile-permissions',
				'name' => 'my-job-cards',
				'display_name' => 'My Job Cards',
			],

		];
		Permission::createFromArrays($permissions);
	}
}