<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AuthAdminController;
use App\Http\Controllers\User\AuthUserController;
use Illuminate\Support\Facades\Route;

/**************************************************************/ 
/******************* User Routes ******************************/ 
/**************************************************************/

// Guest routes (only not-logged-in users)

    Route::post('/user/register', [AuthUserController::class, 'userRegister']);
    Route::post('/user/login', [AuthUserController::class, 'login'])->name('user.login');
    Route::post('/user/forgot-password', [AuthUserController::class, 'forgotPassword']);
    Route::post('/user/reset-password', [AuthUserController::class, 'resetPassword']);
    Route::get('/user/verify-email/{token}', [AuthUserController::class, 'verifyEmail']); 


// Authenticated user routes
Route::middleware('auth:api')->group(function () {
    Route::post('/user/logout', [AuthUserController::class, 'logout']);
    Route::get('/user/verify-token', [AuthUserController::class, 'verifyToken']);
});

/**************************************************************/ 
/******************* Admin Routes *****************************/ 
/**************************************************************/

// Guest routes (only not-logged-in admins)

    Route::post('/admin/login', [AuthAdminController::class, 'login'])->name('admin.login');


// Authenticated admin routes
Route::middleware(['auth:api'])->group(function () {
    Route::post('/admin/logout', [AuthAdminController::class, 'logout']);
    Route::get('/admin/verify-token', [AuthAdminController::class, 'verifyToken']);
    Route::get('/admin/dashboard', [AdminController::class, 'index'])->name('admin.index');
});

// Route::get('/admin/login', [AdminController::class, 'login'])->name('admin.login');


