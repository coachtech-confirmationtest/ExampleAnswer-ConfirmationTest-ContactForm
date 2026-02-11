<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ContactController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// お問い合わせフォーム
Route::get('/', [ContactController::class, 'index']);

// お問い合わせ完了
Route::get('/thanks', [ContactController::class, 'thanks']);

// 管理画面（認証必須）
Route::middleware('auth')->group(function () {
    Route::get('/admin', [AdminController::class, 'index']);
    Route::get('/contacts/export', [ContactController::class, 'export']);
});
