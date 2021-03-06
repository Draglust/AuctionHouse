<?php

use Illuminate\Support\Facades\Route;

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

// Route::get('/', function () {
//     return view('main');
// });
Route::get('/demo', function () {
    return view('demo');
});
Route::get('/scaffolding', function () {
    return view('scaffolding');
});

Route::get('/main', 'DataController@getClasses');
Route::get('/request', 'ConnectionController@getCredentials');
Route::get('/realmapidata', 'ConnectionController@saveConnectedRealmApiData'); //Carga de las url de los reinos
Route::get('/realmapinames', 'ConnectionController@getAndSaveConnectedRealmApiName'); //Carga de los nombres de los reinos en base a las url previas
Route::get('/auctionhouse', 'ConnectionController@getAndSaveConnectedRealmAuctionHouseApiData'); //Carga de todas las subastas "live"
Route::get('/getItems', 'ConnectionController@getAndSaveItemData'); //Carga de todas las subastas "live"
Route::get('/getRecipes', 'ConnectionController@getRecipes'); //Carga de todas las subastas "live"
Route::get('/getProfitRecipes', 'ConnectionController@getProfitRecipes'); //Obtiene las recetas que dan beneficios
Route::get('/getDroppedLocations/{id}', 'ConnectionController@getDropPoints'); //Obtiene la localización de los mobs que sueltan un elemento
Route::get('/getSkinningLocations/{id}', 'ConnectionController@getSkinningPoints'); //Obtiene la localización de los mobs que sueltan un elemento
