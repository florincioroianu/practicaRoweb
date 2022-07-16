<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::middleware(['guest'])->group(function () {
    Route::post('/login', [UserController::class, 'login']);
    Route::post('/register', [UserController::class, 'register']);
    Route::post('/forgot-password', [UserController::class, 'forgotPassword'])->name('password.email');
    Route::get('/reset-password/{token}', function ($token) {
        return $token;
    })->name('password.reset');
    Route::post('/reset-password', [UserController::class, 'resetPassword'])->name('password.update');
});



Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/email/verify', function () {  //
        return;                                //   aici ar trebui sa redirectioneze cand se incearca intrarea pe o ruta ce nu are acces fara verificarea email-ului
    })->name('verification.notice');           //

    Route::get('/email/verify/{id}/{hash}', [UserController::class, 'verifyEmail'])     //  atunci cand dai click pe link-ul primit pe email
    ->middleware(['signed'])->name('verification.verify');                              //

    Route::post('/email/verification-notification', [UserController::class, 'sendVerificationEmail'])   //  ruta ce trimite manual mail-ul
    ->middleware(['throttle:6,1'])->name('verification.send');                                          //

    Route::middleware(['verified'])->group(function () {
        Route::get('/categories', [CategoryController::class, 'getAll']);
        Route::post('/category', [CategoryController::class, 'add']);
        Route::get('/category/{id}', [CategoryController::class, 'get']);
        Route::put('/category/{id}', [CategoryController::class, 'update']);
        Route::delete('/category/{id}', [CategoryController::class, 'delete']);
        Route::get('categories-tree', [CategoryController::class, 'tree']);

        Route::get('/products', [ProductController::class, 'getAll']);
        Route::post('/product', [ProductController::class, 'add']);
        Route::get('/product/{id}', [ProductController::class, 'get']);
        Route::put('/product/{id}', [ProductController::class, 'update']);
        Route::delete('/product/{id}', [ProductController::class, 'delete']);

        Route::get('/products/{categoryId}', [ProductController::class, 'getAllProductsForCategory']);
    });
});
