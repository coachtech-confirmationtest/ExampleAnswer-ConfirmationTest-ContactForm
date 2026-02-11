<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\TagController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// カテゴリー一覧
Route::get('/categories', [CategoryController::class, 'index']);

// お問い合わせ一覧
Route::get('/contacts', [ContactController::class, 'index']);
// お問い合わせ登録
Route::post('/contacts', [ContactController::class, 'store']);
// お問い合わせ詳細
Route::get('/contacts/{contact}', [ContactController::class, 'show']);
// お問い合わせ削除
Route::delete('/contacts/{contact}', [ContactController::class, 'destroy']);

// タグ一覧
Route::get('/tags', [TagController::class, 'index']);
// タグ登録
Route::post('/tags', [TagController::class, 'store']);
// タグ更新
Route::put('/tags/{tag}', [TagController::class, 'update']);
// タグ削除
Route::delete('/tags/{tag}', [TagController::class, 'destroy']);
