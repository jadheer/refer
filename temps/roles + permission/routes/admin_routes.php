<?php

Route::group(['prefix'  =>  'admin', 'as' => 'admin.'], function () {

    Route::get('login', 'Admin\Auth\LoginController@showLoginForm')->name('login');
    Route::post('login', 'Admin\Auth\LoginController@login')->name('login.post');
    Route::get('logout', 'Admin\Auth\LoginController@logout')->name('admin.logout');


    Route::group(['middleware' => ['auth:admin']], function () {

        Route::get('dashboard', function () {
            return view('admin.dashboard');
        })->name('dashboard');
        Route::resource('roles', 'Admin\RoleController');
        Route::resource('permissions', 'Admin\PermissionController');

        Route::resource('admins', 'Admin\AdminController');
        Route::resource('posts', 'Admin\PostController');


    });

});

?>
