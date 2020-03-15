<?php

Route::get('customer-login', 'Customer\LoginController@showLoginForm')->name('customer.login');
Route::post('customer-login', 'Customer\LoginController@login')->name('customer.login.post');
Route::get('logout', 'Customer\LoginController@logout')->name('customer.logout');

Route::group(['prefix'  =>  'customer'], function () {


    Route::group(['middleware' => ['auth:customer']], function () {
        Route::get('/', function () {
            return view('customer.dashboard');
        })->name('customer.dashboard');
    });

});

?>
