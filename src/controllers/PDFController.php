<?php

namespace Abs\GigoPkg;
use Abs\GigoPkg\JobCard;
use DB;
use PDF;
use App\Http\Controllers\Controller;

class PDFController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function gatePass($id) {

		$this->data['gate_pass'] = JobCard::with([
			'gatePasses',
    		'jobOrder',
			'jobOrder.type',
			'jobOrder.vehicle',
			'jobOrder.vehicle.model',
			'jobOrder.vehicle.status',
			'jobOrder.outlet',
			'jobOrder.gateLog',
		    'jobOrder.vehicle.currentOwner.customer',
			'jobOrder.vehicle.currentOwner.customer.address',
			'jobOrder.vehicle.currentOwner.customer.address.country',
			'jobOrder.vehicle.currentOwner.customer.address.state',
			'jobOrder.vehicle.currentOwner.customer.address.city',
		    'jobOrder.serviceType',
		    'jobOrder.jobOrderRepairOrders.repairOrder',
			'jobOrder.jobOrderRepairOrders.repairOrder.repairOrderType',
		    'jobOrder.floorAdviser',
		    'jobOrder.serviceAdviser'])
			->select([
				'job_cards.*',
				DB::raw('DATE_FORMAT(job_cards.created_at,"%d/%m/%Y") as jobdate'),
				DB::raw('DATE_FORMAT(job_cards.created_at,"%h:%i %p") as time'),
			])
			->find($id);

			//dd($this->data['gate_pass']->jobOrder->outlet->gst_number);

	    $pdf = PDF::loadView('pdf-gigo/gate-pass-pdf',$this->data);

		return $pdf->stream('gate-pass-pdf');
	}

}
