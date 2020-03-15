<?php

Route::group(['prefix'  =>  'user'], function () {

    Route::group(['middleware' => ['auth']], function () {

        Route::get('/dashboard', 'User\UserController@dashboard')->name('user.dashboard');

    });
});
