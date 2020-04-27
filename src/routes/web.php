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

});