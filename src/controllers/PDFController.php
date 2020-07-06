<?php

namespace Abs\GigoPkg;
use App\Http\Controllers\Controller;
use PDF;

class PDFController extends Controller {
	public function getJobCardCoveringLetterPDF($id) {

		//$pdf = PDF::loadView('static/gigo/covering-letter-pdf');
		dd($id);
		$pdf = PDF::loadView('job-card/covering-letter-pdf');

		return $pdf->stream('covering-letter-pdf');
	}
}
