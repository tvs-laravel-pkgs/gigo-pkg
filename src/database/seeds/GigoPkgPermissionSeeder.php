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

			//Fault
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'faults',
				'display_name' => 'Faults',
			],
			[
				'display_order' => 1,
				'parent' => 'faults',
				'name' => 'add-fault',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'faults',
				'name' => 'edit-fault',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'faults',
				'name' => 'delete-fault',
				'display_name' => 'Delete',
			],

			//Parts Indent
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'parts-indent',
				'display_name' => 'Parts Indent',
			],
			[
				'display_order' => 1,
				'parent' => 'parts-indent',
				'name' => 'view-parts-indent',
				'display_name' => 'View',
			],

			//Complaint Group
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'complaint-groups',
				'display_name' => 'Complaint Group',
			],
			[
				'display_order' => 1,
				'parent' => 'complaint-groups',
				'name' => 'add-complaint-group',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'complaint-groups',
				'name' => 'edit-complaint-group',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'complaint-groups',
				'name' => 'delete-complaint-group',
				'display_name' => 'Delete',
			],

			//Complaint
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'complaint',
				'display_name' => 'Complaint',
			],
			[
				'display_order' => 1,
				'parent' => 'complaint',
				'name' => 'add-complaint',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'complaint',
				'name' => 'edit-complaint',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'complaint',
				'name' => 'delete-complaint',
				'display_name' => 'Delete',
			],

			//Vehicle Secoundary Application
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'vehicle-secoundary-applications',
				'display_name' => 'Vehicle Secounday Application',
			],
			[
				'display_order' => 1,
				'parent' => 'vehicle-secoundary-applications',
				'name' => 'add-vehicle-secoundary-application',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'vehicle-secoundary-applications',
				'name' => 'edit-vehicle-secoundary-application',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'vehicle-secoundary-applications',
				'name' => 'delete-vehicle-secoundary-application',
				'display_name' => 'Delete',
			],

			//Pause Work Reason
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'pause-work-reasons',
				'display_name' => 'Pause Work Reason',
			],
			[
				'display_order' => 1,
				'parent' => 'pause-work-reasons',
				'name' => 'add-pause-work-reason',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'pause-work-reasons',
				'name' => 'edit-pause-work-reason',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'pause-work-reasons',
				'name' => 'delete-pause-work-reason',
				'display_name' => 'Delete',
			],

			//Service Types
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'service-types',
				'display_name' => 'Service Types',
			],
			[
				'display_order' => 1,
				'parent' => 'service-types',
				'name' => 'add-service-type',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'service-types',
				'name' => 'edit-service-type',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'service-types',
				'name' => 'delete-service-type',
				'display_name' => 'Delete',
			],

			//Service Order Types
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'service-order-types',
				'display_name' => 'Service Order Types',
			],
			[
				'display_order' => 1,
				'parent' => 'service-order-types',
				'name' => 'add-service-order-type',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'service-order-types',
				'name' => 'edit-service-order-type',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'service-order-types',
				'name' => 'delete-service-order-type',
				'display_name' => 'Delete',
			],

			//Vehicle Owners
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'vehicle-owners',
				'display_name' => 'Vehicle Owners',
			],
			[
				'display_order' => 1,
				'parent' => 'vehicle-owners',
				'name' => 'add-vehicle-owner',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'vehicle-owners',
				'name' => 'edit-vehicle-owner',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'vehicle-owners',
				'name' => 'delete-vehicle-owner',
				'display_name' => 'Delete',
			],

			//Amc Members
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'amc-members',
				'display_name' => 'Amc Members',
			],
			[
				'display_order' => 1,
				'parent' => 'amc-members',
				'name' => 'add-amc-member',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'amc-members',
				'name' => 'edit-amc-member',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'amc-members',
				'name' => 'delete-amc-member',
				'display_name' => 'Delete',
			],

			//Vehicle Warranty Members
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'vehicle-warranty-members',
				'display_name' => 'Vehicle Warranty Members',
			],
			[
				'display_order' => 1,
				'parent' => 'vehicle-warranty-members',
				'name' => 'add-vehicle-warranty-member',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'vehicle-warranty-members',
				'name' => 'edit-vehicle-warranty-member',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'vehicle-warranty-members',
				'name' => 'delete-vehicle-warranty-member',
				'display_name' => 'Delete',
			],

			//Insurance Members
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'insurance-members',
				'display_name' => 'Insurance Members',
			],
			[
				'display_order' => 1,
				'parent' => 'insurance-members',
				'name' => 'add-insurance-member',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'insurance-members',
				'name' => 'edit-insurance-member',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'insurance-members',
				'name' => 'delete-insurance-member',
				'display_name' => 'Delete',
			],

			//Quote Types
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'quote-types',
				'display_name' => 'Quote Types',
			],
			[
				'display_order' => 1,
				'parent' => 'quote-types',
				'name' => 'add-quote-type',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'quote-types',
				'name' => 'edit-quote-type',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'quote-types',
				'name' => 'delete-quote-type',
				'display_name' => 'Delete',
			],

			//Vehicle Inventory Items
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'vehicle-inventory-items',
				'display_name' => 'Vehicle Inventory Items',
			],
			[
				'display_order' => 1,
				'parent' => 'vehicle-inventory-items',
				'name' => 'add-vehicle-inventory-item',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'vehicle-inventory-items',
				'name' => 'edit-vehicle-inventory-item',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'vehicle-inventory-items',
				'name' => 'delete-vehicle-inventory-item',
				'display_name' => 'Delete',
			],

			//Vehicle Inspection Item Groups
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'vehicle-inspection-item-groups',
				'display_name' => 'Vehicle Inspection Item Groups',
			],
			[
				'display_order' => 1,
				'parent' => 'vehicle-inspection-item-groups',
				'name' => 'add-vehicle-inspection-item-group',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'vehicle-inspection-item-groups',
				'name' => 'edit-vehicle-inspection-item-group',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'vehicle-inspection-item-groups',
				'name' => 'delete-vehicle-inspection-item-group',
				'display_name' => 'Delete',
			],

			//Vehicle Inspection Items
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'vehicle-inspection-items',
				'display_name' => 'Vehicle Inspection Items',
			],
			[
				'display_order' => 1,
				'parent' => 'vehicle-inspection-items',
				'name' => 'add-vehicle-inspection-item',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'vehicle-inspection-items',
				'name' => 'edit-vehicle-inspection-item',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'vehicle-inspection-items',
				'name' => 'delete-vehicle-inspection-item',
				'display_name' => 'Delete',
			],

			//Customer Voices
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'customer-voices',
				'display_name' => 'Customer Voices',
			],
			[
				'display_order' => 1,
				'parent' => 'customer-voices',
				'name' => 'add-customer-voice',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'customer-voices',
				'name' => 'edit-customer-voice',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'customer-voices',
				'name' => 'delete-customer-voice',
				'display_name' => 'Delete',
			],

			//Split Order Types
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'split-order-types',
				'display_name' => 'Split Order Types',
			],
			[
				'display_order' => 1,
				'parent' => 'split-order-types',
				'name' => 'add-split-order-type',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'split-order-types',
				'name' => 'edit-split-order-type',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'split-order-types',
				'name' => 'delete-split-order-type',
				'display_name' => 'Delete',
			],

			//Bays
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'bays',
				'display_name' => 'Bays',
			],
			[
				'display_order' => 1,
				'parent' => 'bays',
				'name' => 'add-bay',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'bays',
				'name' => 'edit-bay',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'bays',
				'name' => 'delete-bay',
				'display_name' => 'Delete',
			],

			//Estimation Types
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'estimation-types',
				'display_name' => 'Estimation Types',
			],
			[
				'display_order' => 1,
				'parent' => 'estimation-types',
				'name' => 'add-estimation-type',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'estimation-types',
				'name' => 'edit-estimation-type',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'estimation-types',
				'name' => 'delete-estimation-type',
				'display_name' => 'Delete',
			],

			//Gate Passes
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'gate-passes',
				'display_name' => 'Gate Passes',
			],
			[
				'display_order' => 1,
				'parent' => 'gate-passes',
				'name' => 'add-gate-pass',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'gate-passes',
				'name' => 'edit-gate-pass',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'gate-passes',
				'name' => 'delete-gate-pass',
				'display_name' => 'Delete',
			],

			//Gate Logs
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'gate-logs',
				'display_name' => 'Gate Logs',
			],
			[
				'display_order' => 1,
				'parent' => 'gate-logs',
				'name' => 'add-gate-log',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'gate-logs',
				'name' => 'edit-gate-log',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'gate-logs',
				'name' => 'delete-gate-log',
				'display_name' => 'Delete',
			],

			//VEHICLE GATE PASS
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'vehicle-gate-passes',
				'display_name' => 'Vehicle Gate Passes',
			],
			[
				'display_order' => 1,
				'parent' => 'vehicle-gate-passes',
				'name' => 'view-vehicle-gate-pass',
				'display_name' => 'View',
			],
			[
				'display_order' => 2,
				'parent' => 'vehicle-gate-passes',
				'name' => 'gate-out-vehicle-gate-pass',
				'display_name' => 'Gate Out',
			],

			//MATERIAl GATE PASS
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'material-gate-passes',
				'display_name' => 'Material Gate Passes',
			],
			[
				'display_order' => 1,
				'parent' => 'material-gate-passes',
				'name' => 'view-material-gate-pass',
				'display_name' => 'View',
			],
			[
				'display_order' => 2,
				'parent' => 'material-gate-passes',
				'name' => 'gate-out-material-gate-pass',
				'display_name' => 'Gate Out',
			],
			[
				'display_order' => 3,
				'parent' => 'material-gate-passes',
				'name' => 'gate-in-material-gate-pass',
				'display_name' => 'Gate In',
			],

			//Repair Order
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'repair-orders',
				'display_name' => 'Repair Order',
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
				'name' => 'delete-repair-order',
				'display_name' => 'Delete',
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
				'name' => 'delete-repair-order-type',
				'display_name' => 'Delete',
			],

			//Vehicle Inward
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'inward-vehicle',
				'display_name' => 'Inward Vehicle',
			],
			[
				'display_order' => 1,
				'parent' => 'inward-vehicle',
				'name' => 'add-vehicle-inward',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'inward-vehicle',
				'name' => 'edit-vehicle-inward',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'inward-vehicle',
				'name' => 'delete-vehicle-inward',
				'display_name' => 'Delete',
			],
			[
				'display_order' => 4,
				'parent' => 'inward-vehicle',
				'name' => 'view-own-outlet-vehicle-inward',
				'display_name' => 'View Own Outlet',
			],
			[
				'display_order' => 5,
				'parent' => 'inward-vehicle',
				'name' => 'view-mapped-outlet-vehicle-inward',
				'display_name' => 'View Mapped Outlets',
			],
			[
				'display_order' => 6,
				'parent' => 'inward-vehicle',
				'name' => 'view-overall-outlets-vehicle-inward',
				'display_name' => 'View Overall Outlets',
			],

			//My JobCard
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'my-jobcard',
				'display_name' => 'My JobCard List',
			],

			//Job Order Repair Orders
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'job-order-repair-orders',
				'display_name' => 'Job Order Repair Orders',
			],
			[
				'display_order' => 1,
				'parent' => 'job-order-repair-orders',
				'name' => 'add-job-order-repair-order',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'job-order-repair-orders',
				'name' => 'edit-job-order-repair-order',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'job-order-repair-orders',
				'name' => 'delete-job-order-repair-order',
				'display_name' => 'Delete',
			],

			//Repair Order Mechanics
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'repair-order-mechanics',
				'display_name' => 'Repair Order Mechanics',
			],
			[
				'display_order' => 1,
				'parent' => 'repair-order-mechanics',
				'name' => 'add-repair-order-mechanic',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'repair-order-mechanics',
				'name' => 'edit-repair-order-mechanic',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'repair-order-mechanics',
				'name' => 'delete-repair-order-mechanic',
				'display_name' => 'Delete',
			],

			//Mechanic Time Logs
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'mechanic-time-logs',
				'display_name' => 'Mechanic Time Logs',
			],
			[
				'display_order' => 1,
				'parent' => 'mechanic-time-logs',
				'name' => 'add-mechanic-time-log',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'mechanic-time-logs',
				'name' => 'edit-mechanic-time-log',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'mechanic-time-logs',
				'name' => 'delete-mechanic-time-log',
				'display_name' => 'Delete',
			],

			//Job Order Parts
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'job-order-parts',
				'display_name' => 'Job Order Parts',
			],
			[
				'display_order' => 1,
				'parent' => 'job-order-parts',
				'name' => 'add-job-order-part',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'job-order-parts',
				'name' => 'edit-job-order-part',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'job-order-parts',
				'name' => 'delete-job-order-part',
				'display_name' => 'Delete',
			],

			//Job Order Issued Parts
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'job-order-issued-parts',
				'display_name' => 'Job Order Issued Parts',
			],
			[
				'display_order' => 1,
				'parent' => 'job-order-issued-parts',
				'name' => 'add-job-order-issued-part',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'job-order-issued-parts',
				'name' => 'edit-job-order-issued-part',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'job-order-issued-parts',
				'name' => 'delete-job-order-issued-part',
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
				'name' => 'delete-job-card',
				'display_name' => 'Delete',
			],

			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'mobile-simulation',
				'display_name' => 'Mobile Simulation',
			],

			//Mobile Permissions
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'mobile-permissions',
				'display_name' => 'Mobile App',
			],

			//KANBAN App
			[
				'display_order' => 99,
				'parent' => 'mobile-permissions',
				'name' => 'kanban-app',
				'display_name' => 'KANBAN App',
			],
			[
				'display_order' => 1,
				'parent' => 'kanban-app',
				'name' => 'mobile-attendanace',
				'display_name' => 'Attendance',
			],
			[
				'display_order' => 99,
				'parent' => 'mobile-permissions',
				'name' => 'mobile-my-job-cards',
				'display_name' => 'My Job Cards',
			],
			[
				'display_order' => 1,
				'parent' => 'kanban-app',
				'name' => 'mobile-my-time-sheet',
				'display_name' => 'My Time Sheet',
			],

			//Gate In Entry
			[
				'display_order' => 99,
				'parent' => 'mobile-permissions',
				'name' => 'mobile-gate-in-entry',
				'display_name' => 'Gate In Entry',
			],
			[
				'display_order' => 1,
				'parent' => 'mobile-gate-in-entry',
				'name' => 'mobile-gate-in-entry-view-all',
				'display_name' => 'View All',
			],
			[
				'display_order' => 2,
				'parent' => 'mobile-gate-in-entry',
				'name' => 'mobile-gate-in-entry-outlet-based',
				'display_name' => 'Outlet Based',
			],
			[
				'display_order' => 3,
				'parent' => 'mobile-gate-in-entry',
				'name' => 'mobile-gate-in-entry-own-only',
				'display_name' => 'Own Only',
			],

			//Gate Out Entry
			[
				'display_order' => 99,
				'parent' => 'mobile-permissions',
				'name' => 'mobile-gate-out-entry',
				'display_name' => 'Gate Out Entry',
			],
			[
				'display_order' => 1,
				'parent' => 'mobile-gate-out-entry',
				'name' => 'mobile-gate-out-entry-view-all',
				'display_name' => 'View All',
			],
			[
				'display_order' => 2,
				'parent' => 'mobile-gate-in-entry',
				'name' => 'mobile-gate-out-entry-outlet-based',
				'display_name' => 'Working Outlet Based',
			],
			[
				'display_order' => 3,
				'parent' => 'mobile-gate-in-entry',
				'name' => 'mobile-gate-out-entry-own-only',
				'display_name' => 'Own Only',
			],

			//Inward Vehicle
			[
				'display_order' => 99,
				'parent' => 'mobile-permissions',
				'name' => 'mobile-inward-vehicle',
				'display_name' => 'Inward Vehicle',
			],
			[
				'display_order' => 1,
				'parent' => 'mobile-gate-in-entry',
				'name' => 'mobile-inward-vehicle-view-all',
				'display_name' => 'View All',
			],
			[
				'display_order' => 2,
				'parent' => 'mobile-gate-in-entry',
				'name' => 'mobile-inward-vehicle-outlet-based',
				'display_name' => 'Working Outlet Based',
			],
			[
				'display_order' => 3,
				'parent' => 'mobile-gate-in-entry',
				'name' => 'mobile-inward-vehicle-own-only',
				'display_name' => 'Own Only',
			],

			//Job Cards
			[
				'display_order' => 99,
				'parent' => 'mobile-permissions',
				'name' => 'mobile-job-cards',
				'display_name' => 'Job Cards',
			],
			[
				'display_order' => 1,
				'parent' => 'mobile-gate-in-entry',
				'name' => 'mobile-job-cards-view-all',
				'display_name' => 'View All',
			],
			[
				'display_order' => 2,
				'parent' => 'mobile-gate-in-entry',
				'name' => 'mobile-job-cards-outlet-based',
				'display_name' => 'Working Outlet Based',
			],
			[
				'display_order' => 3,
				'parent' => 'mobile-gate-in-entry',
				'name' => 'mobile-job-cards-own-only',
				'display_name' => 'Own Only',
			],

			//Material Gate Passes
			[
				'display_order' => 99,
				'parent' => 'mobile-permissions',
				'name' => 'mobile-material-gate-passes',
				'display_name' => 'Material Gate Passes',
			],
			[
				'display_order' => 1,
				'parent' => 'mobile-gate-in-entry',
				'name' => 'mobile-material-gate-passes-view-all',
				'display_name' => 'View All',
			],
			[
				'display_order' => 2,
				'parent' => 'mobile-gate-in-entry',
				'name' => 'mobile-material-gate-passes-outlet-based',
				'display_name' => 'Working Outlet Based',
			],
			[
				'display_order' => 3,
				'parent' => 'mobile-gate-in-entry',
				'name' => 'mobile-material-gate-passes-own-only',
				'display_name' => 'Own Only',
			],

			//Campaign
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'campaign',
				'display_name' => 'Bays',
			],
			[
				'display_order' => 1,
				'parent' => 'campaign',
				'name' => 'add-campaign',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'campaign',
				'name' => 'edit-campaign',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'campaign',
				'name' => 'delete-campaign',
				'display_name' => 'Delete',
			],

		];
		Permission::createFromArrays($permissions);
	}
}