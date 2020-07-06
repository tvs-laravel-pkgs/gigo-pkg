<?php

namespace Abs\GigoPkg;
use Abs\GigoPkg\JobCard;
use App\Http\Controllers\Controller;
use PDF;

class PDFController extends Controller {

	public function getJobCardCoveringLetterPDF($id) {

		//$pdf = PDF::loadView('static/gigo/covering-letter-pdf');
		dd($id);
		$pdf = PDF::loadView('job-card/covering-letter-pdf');

		return $pdf->stream('covering-letter-pdf');
	}
    
    public function getjobCardGatePassPDF($jobard_id){

    	$this->data['gate_pass'] = JobCard::with([
    		'JobCard.gatePasses',
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
				DB::raw('DATE_FORMAT(job_cards.created_at,"%d/%m/%Y") as date'),
				DB::raw('DATE_FORMAT(job_cards.created_at,"%h:%i %p") as time'),
			])
			->find($jobard_id);

       $pdf = PDF::loadView('job-card/pdf/gate-pass-pdf',$this->data);

		return $pdf->stream('gate-pass-pdf');
    } 
   

}
