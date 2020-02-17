<?php

Auth::routes();

require 'admin_routes.php';
require 'user_routes.php';

Route::get('/', function () {
    return view('web.home');
});
Route::get('/home', 'HomeController@index')->name('home');




