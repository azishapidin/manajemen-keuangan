<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect()->route('login');
});

Auth::routes();
Route::get('lang/{lang}', 'RouteHandler\LanguageController@switchLang')->name('lang.switch');

Route::get('/home', 'RouteHandler\HomeController@index')->name('home');

// Account Controller
Route::resource('/account', 'RouteHandler\AccountController');
Route::get('/account/{id}/restore', 'RouteHandler\AccountController@restore')->name('account.restore');
Route::delete('/account/{id}/permanent', 'RouteHandler\AccountController@deletePermanent')->name('account.deletePermanent');

// Transaction Controller
Route::get('transaction/', 'RouteHandler\TransactionController@index')->name('transaction.index');
Route::get('transaction/{transactionId}/detail', 'RouteHandler\TransactionController@detail')->name('transaction.detail');
Route::get('/account/{accountId}/add', 'RouteHandler\TransactionController@create')->name('transaction.create');
Route::post('/account/{accountId}/add', 'RouteHandler\TransactionController@store')->name('transaction.store');
