<?php

namespace Abs\GigoPkg;
use Abs\GigoPkg\JobCard;
use App\Http\Controllers\Controller;

class PDFController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function gatePass($id) {

		//$pdf = PDF::loadView('static/gigo/covering-letter-pdf');
		dd($id);

		$pdf = PDF::loadView('job-card/covering-letter-pdf');

		return $pdf->stream('covering-letter-pdf');
	}

}
