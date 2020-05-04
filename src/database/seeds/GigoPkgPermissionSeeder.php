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

			//Repair Order Types
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'repair-order-types',
				'display_name' => 'Repair Order Types',
			],
			[
				'display_order' => 1,
				'parent' => 'repair-order-types',
				'name' => 'add-repair-order-type',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'repair-order-types',
				'name' => 'edit-repair-order-type',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'repair-order-types',
				'name' => 'view-repair-order-type',
				'display_name' => 'View',
			],
			[
				'display_order' => 4,
				'parent' => 'repair-order-types',
				'name' => 'delete-repair-order-type',
				'display_name' => 'Delete',
			],

			//Repair Orders
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'repair-orders',
				'display_name' => 'Repair Orders',
			],
			[
				'display_order' => 1,
				'parent' => 'repair-orders',
				'name' => 'add-repair-order',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'repair-orders',
				'name' => 'edit-repair-order',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'repair-orders',
				'name' => 'view-repair-order',
				'display_name' => 'View',
			],
			[
				'display_order' => 4,
				'parent' => 'repair-orders',
				'name' => 'delete-repair-order',
				'display_name' => 'Delete',
			],

			//SHIFTS
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'shifts',
				'display_name' => 'Shifts',
			],
			[
				'display_order' => 1,
				'parent' => 'shifts',
				'name' => 'add-shift',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'shifts',
				'name' => 'edit-shift',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'shifts',
				'name' => 'delete-shift',
				'display_name' => 'Delete',
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