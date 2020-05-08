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

//Cargando clases
use App\Http\Middleware\ApiAuthMiddleware;

Route::get('/', function () {
    return view('welcome');
});
//RUTAS DE PRUEBA
Route::get('/pruebas/{nombre?}', function($nombre = null) {

    $texto = '<h2>Texto desde una ruta</h2>';
    $texto .= 'Nombre: ' . $nombre;

    return view('pruebas', array(
        'texto' => $texto
    ));
});


Route::get('/animales', 'PruebasController@index');
Route::get('/testOrm', 'PruebasController@testOrm');

//RUTAS DEL API

/* metofdos HTTP comunes
 * GET: conseguir datos o recursos
 * POST: guardar datos o recursos o hacer logica desde el formulario
 * PUT: actualizar dtaos o recursos
 * DELETE: eliminar datos o recursos
 */

//rutas de prueba
//Route::get('/usuario/pruebas', 'UserController@pruebas');
//Route::get('/categoria/pruebas', 'CategoryController@pruebas');
//Route::get('/entrada/pruebas', 'PostController@pruebas');

//rutas del controlador de usuario
Route::post('/api/register', 'UserController@register');
Route::post('/api/login', 'UserController@login');
Route::put('/api/user/update', 'UserController@update');
Route::post('/api/user/upload', 'UserController@upload')->middleware(ApiAuthMiddleware::class); //primero se ejecuta el middleware
Route::get('/api/user/avatar/{filename}', 'UserController@getImage');
Route::get('/api/user/detail/{id}', 'UserController@detail');

//rutas del controlador de categorias
Route::resource('/api/category','CategoryController');

// rutas del controlador de entradas osea los posts del blog
Route::resource('/api/post','PostController');
Route::post('/api/post/upload', 'PostController@upload');
Route::get('/api/post/image/{filename}', 'PostController@getImage');
Route::get('/api/post/category/{id}', 'PostController@getPostsByCategory');
Route::get('/api/post/user/{id}', 'PostController@getPostsByUser');