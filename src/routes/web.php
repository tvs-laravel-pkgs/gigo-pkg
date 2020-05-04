<?php

Route::group(['namespace' => 'Abs\GigoPkg', 'middleware' => ['web', 'auth'], 'prefix' => 'gigo-pkg'], function () {

	//Job Cards
	Route::get('/job-cards/get-list', 'JobCardController@getJobCardList')->name('getJobCardList');
	Route::get('/job-cards/get-form-data', 'JobCardController@getJobCardFormData')->name('getJobCardFormData');
	Route::post('/job-cards/save', 'JobCardController@saveJobCard')->name('saveJobCard');
	Route::get('/job-cards/delete', 'JobCardController@deleteJobCard')->name('deleteJobCard');
	Route::get('/job-cards/view', 'JobCardController@getJobCardView')->name('getJobCardView');
	Route::get('/job-cards/get-filter', 'JobCardController@getJvFilterData')->name('getJvFilterData');
	Route::get('/job-card/get', 'JobCardController@getJobCard')->name('getJobCard');


	//Repair Order Types
	Route::get('/repair-order-type/get-list', 'RepairOrderTypeController@getRepairOrderTypeList')->name('getRepairOrderTypeList');
	Route::get('/repair-order-type/get-form-data', 'RepairOrderTypeController@getRepairOrderTypeFormData')->name('getRepairOrderTypeFormData');
    Route::post('/repair-order-type/form-save', 'RepairOrderTypeController@saveRepairOrderType')->name('saveRepairOrderType');
    Route::get('/repair-order-type/get-filter', 'RepairOrderTypeController@getRepairOrderTypeFilter')->name('getRepairOrderTypeFilter');
    Route::get('/repair-order-type/delete', 'RepairOrderTypeController@deleteRepairOrderType')->name('deleteRepairOrderType');
    Route::get('/repair-order-type/view', 'RepairOrderTypeController@getRepairOrderTypeView')->name('getRepairOrderTypeView');


    //Repair Order 
    Route::get('/repair-order/get-form-data', 'RepairOrderController@getRepairOrderFormData')->name('getRepairOrderFormData');
    Route::post('/repair-order/form-save', 'RepairOrderController@saveRepairOrder')->name('saveRepairOrder');
    Route::get('/repair-order/get-list', 'RepairOrderController@getRepairOrderList')->name('getRepairOrderList');
    Route::get('/repair-order/get-filter', 'RepairOrderController@getRepairOrderFilter')->name('getRepairOrderFilter');
    Route::get('/repair-order/delete', 'RepairOrderController@deleteRepairOrder')->name('deleteRepairOrder');

});