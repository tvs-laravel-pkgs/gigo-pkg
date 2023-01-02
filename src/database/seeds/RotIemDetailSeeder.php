<?php
namespace Abs\GigoPkg\Database\Seeds;

use Illuminate\Database\Seeder;
use App\RotIemDetail;

use Excel;

class RotIemDetailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        dump('Started');
        Excel::selectSheetsByIndex(0)->load('public/excel-imports/RotItemDetails.xlsx', function ($reader) {
            $reader->limitRows(3000);
            $reader->limitColumns(14);
            $records = $reader->get();
            foreach ($records as $key => $record) {
                $errors = [];

                $company_id = 4;    // 4-> TMD
                $business_id = 30;    // 30-> CEMH
                
                if (!isset($record->rot_code) || !$record->rot_code)
                    $errors[] = 'Row No:' . ($key + 1) . ' Invalid Company Code:' . $record->company_code;
                    
                if (count($errors) == 0) {
                    $rotIemDetail = RotIemDetail::firstOrNew([
                        'company_id' => $company_id,
                        'rot_code' => $record->rot_code,
                    ]);
                    $rotIemDetail->company_id = $company_id;
                    $rotIemDetail->business_id = $business_id;
                    $rotIemDetail->rot_code = $record->rot_code;
                    $rotIemDetail->job_group = (isset($record->job_group) && $record->job_group) ? $record->job_group : null;
                    $rotIemDetail->name = (isset($record->nature_of_jobs) && $record->nature_of_jobs) ? $record->nature_of_jobs : null;
                    $rotIemDetail->km = (isset($record->approkm) && $record->approkm) ? $record->approkm : null;
                    $rotIemDetail->man_days = (isset($record->mandays) && $record->mandays) ? $record->mandays : null;
                    $rotIemDetail->working_hrs_start_time = (isset($record->working_hours_starting_time) && $record->working_hours_starting_time) ? $record->working_hours_starting_time : null;
                    $rotIemDetail->working_hrs_close_time = (isset($record->working_hours_closing_time) && $record->working_hours_closing_time) ? $record->working_hours_closing_time : null;
                    $rotIemDetail->total_working_hrs = (isset($record->total_workinghours) && $record->total_workinghours) ? $record->total_workinghours : null;
                    $rotIemDetail->onsite_price = (isset($record->price_for_onsite_work) && $record->price_for_onsite_work) ? $record->price_for_onsite_work : null;
                    $rotIemDetail->rehab_price = (isset($record->price_for_rehabwork) && $record->price_for_rehabwork) ? $record->price_for_rehabwork : null;
                    $rotIemDetail->remarks = (isset($record->remarks) && $record->remarks) ? $record->remarks : null;
                    $rotIemDetail->save();
                } else {
                    dump($errors);
                }
            }
        });
        dump('Completed');
    }
}
