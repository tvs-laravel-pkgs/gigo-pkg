<?php

namespace Abs\GigoPkg;

use App\BatteryLoadTestStatus;
use App\BatteryMake;
use App\Config;
use App\Http\Controllers\Controller;
use App\HydrometerElectrolyteStatus;
use App\LoadTestStatus;
use App\VehicleBattery;
use App\Vehicle;
use App\Tool;
use Auth;
use DB;
use Entrust;
use Excel;
use Validator;
use Storage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;

class ToolsController extends Controller
{

    public function __construct()
    {
        $this->data['theme'] = config('custom.theme');
    }

    public function getList(Request $request)
    {
        $tools_list = Tool::select(
                'code',
                'name'
            )
            ->where('company_id', Auth::user()->company_id)
        // ->get()
        ;

        $tools_list->orderBy('code', 'ASC');

        return Datatables::of($tools_list)
            ->make(true);
    }

    public function importTools(Request $request) {
		// dd($request->all());

		ini_set('max_execution_time', 0);
		ini_set('memory_limit', '-1');
		$empty_rows = 0;
		$successCount = 0;
		$errorCount = 0;
		$errors = [];
		$error_str = '';

		$input_requests = $request->all();
		$validator = Validator::make($request->all(), [
			'input_excel' => 'required',
		]);

		if ($validator->fails()) {
			$message = ['success' => false, 'errors' => ["Please upload File"]];
			return response()->json($message);
		}

		$extension = $request->file('input_excel')->getClientOriginalExtension();
		if ($extension != "xlsx" && $extension != "xls") {
			$message = ['success' => false, 'errors' => ["Please Import Excel Format File"]];
			return response()->json($message);
		}

		$attachment = 'input_excel';
		$file = $request->file($attachment)->getRealPath();
		$headers = Excel::selectSheetsByIndex(0)->load($file, function ($reader) {
			$reader->takeRows(1);
		})->toArray();

		//return($headers);
		$mandatory_fields = [
			'code',
			'name',
		];
		$missing_fields = [];
		$missing_fields = [0 => 'Invalid File, Mandatory fields are missing.'];
		foreach ($mandatory_fields as $mandatory_field) {
			if (!array_key_exists($mandatory_field, $headers[0])) {
				$missing_fields[] = $mandatory_field;
			}
		}

		if (count($missing_fields) > 1) {
			$response = ['success' => false, 'error' => "Invalid File, Mandatory fields are missing.", 'errors' => $missing_fields];
			return response()->json($response);
		}

		$destination = config('custom.tools_file_path');
		$time_stamp = date('Y_m_d_h_i_s');
		$file_name = $time_stamp . '_tools_import' . '.' . $extension;
		Storage::makeDirectory($destination, 0777);
		$request->file($attachment)->storeAs($destination, $file_name);

		//creating ERROR OUTPUT EXCEL
		$outputfile = $time_stamp . '_Tools_import_report';
		Excel::create($outputfile, function ($excel) use ($headers) {
			$excel->sheet('Error Report', function ($sheet) use ($headers) {
				$headings = array_keys($headers[0]);
				$headings[] = 'Record No';
				$headings[] = 'Error Details';
				$sheet->fromArray(array(
					$headings,
				));
			});
		})->store('xlsx', storage_path('app/public/gigo/tools/'));

		$total_records = Excel::selectSheetsByIndex(0)->load(getUploadedToolsExcel($file_name), function ($reader) {
			$reader->limitColumns(1);
		})->get();
		$total_records = count($total_records);

		$response = [
			'success' => true,
			'total_records' => $total_records,
			'file' => getUploadedToolsExcel($file_name),
			'outputfile' => 'storage/app/public/gigo/tools/' . $outputfile . '.xlsx',
			'error_report_url' => asset(getUploadedToolsExcel($outputfile . '.xlsx')),
			'reference' => $time_stamp,
			'errorCount' => $errorCount,
			'successCount' => $successCount,
			'errors' => $error_str,
		];
		return response()->json($response);

	}

	public function chunkImportTools(Request $request) {
		// dd($request->all());
		$error_str = '';
		$errors = array();
		$status_error_msg = array();
		$error_msg = array();
		$error_count = 0;
		$successCount = 0;
		$newCount = 0;
		$updatedCount = 0;
		$records = 0;
		$empty_rows = 0;
		$file = $request->file;
		$total_records = $request->total_records;
		$skip = $request->skip;
		$records = array();
		$output_file = $request->outputfile;
		$records_per_request = $request->records_per_request;
		$timetamp = $request->reference;

		try {
			$headers = Excel::selectSheetsByIndex(0)->load($file, function ($reader) use ($skip, $records_per_request) {
				$reader->skipRows($skip)->takeRows($records_per_request);
			})->toArray();
		} catch (\Exception $e) {
			$response = ['success' => false, 'error' => $e->getMessage()];
			return response()->json($response);
		}

		$all_error_records = [];
		$errorCount = 0;

		$k = 0;

		foreach ($headers as $key => $record) {
			// dd($record);
			$original_record = $record;
			$k = $skip + $k;
			$skip = false;

			$record_errors = [];
			
			if (empty($record['code'])) {
				$record_errors[] = 'Part Code is Empty';
				$skip = true;
			}

            if (empty($record['name'])) {
				$record_errors[] = 'Part Description is Empty';
				$skip = true;
			}

			if (!$skip) {
				$tool = Tool::firstOrNew(['code'=> $record['code'],'company_id' => Auth::user()->company_id]);

                if($tool->exists){
                    $tool->updated_by_id = Auth::user()->id;
                    $tool->updated_at = Carbon::now();
                }else{
                    $tool->created_at = Carbon::now();
                    $tool->created_by_id = Auth::user()->id;
                    $tool->updated_at = null;
                }
                $tool->name = $record['name'];
                $tool->save();
				
				$newCount++;
			} else {
				$errorCount++;
				$error_str .= '
                 <div class="mue_errortable_line">
                <span class="mue_ticketerror">Record No:' . ($k + 1) . '</span>
                <span class="mue_rowerror">Reason: ' . implode(',', $record_errors) . '</span>
                </div>
                    ';
			}

			if (count($record_errors) > 0) {
				$original_record['Record No'] = $k + 1;
				$original_record['Error Details'] = implode(',', $record_errors);
				$all_error_records[] = $original_record;
			}

		}

		Excel::load($request->outputfile, function ($excel) use ($all_error_records) {
			$excel->sheet('Error Report', function ($sheet) use ($all_error_records) {
				foreach ($all_error_records as $error) {
					$sheet->appendRow($error, null, 'A1', false, false);
				}
			});
		})->store('xlsx', storage_path('app/public/gigo/tools/'));

		$response = ['success' => true, 'processed' => count($headers), 'errors' => $error_str,
			'newCount' => $newCount, 'updatedCount' => $updatedCount, 'errorCount' => $errorCount];
		return response()->json($response);
	}
}
