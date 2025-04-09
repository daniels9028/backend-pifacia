<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\BorrowingController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::apiResource('role', RoleController::class)->middleware('auth:sanctum');
Route::apiResource('account', UserController::class)->middleware('auth:sanctum');

Route::apiResource('book', BookController::class)->middleware('auth:sanctum');
Route::post('/book/export', [BookController::class, 'export'])->middleware('auth:sanctum');
Route::post('/book/import', [BookController::class, 'import'])->middleware('auth:sanctum');
Route::get('/books/audit', [BookController::class, 'audit'])->middleware('auth:sanctum');


Route::apiResource('member', MemberController::class)->middleware('auth:sanctum');
Route::post('/member/export', [MemberController::class, 'export'])->middleware('auth:sanctum');
Route::post('/member/import', [MemberController::class, 'import'])->middleware('auth:sanctum');
Route::get('/members/audit', [MemberController::class, 'audit'])->middleware('auth:sanctum');

Route::apiResource('borrowing', BorrowingController::class)->middleware('auth:sanctum');
Route::get('/borrowings/form-options', [BorrowingController::class, 'getFormOptions'])->middleware('auth:sanctum');
Route::post('/borrowing/export', [BorrowingController::class, 'export'])->middleware('auth:sanctum');
Route::post('/borrowing/import', [BorrowingController::class, 'import'])->middleware('auth:sanctum');
Route::get('/borrowings/audit', [BorrowingController::class, 'audit'])->middleware('auth:sanctum');

Route::get('/audit', [UserController::class, 'audit'])->middleware('auth:sanctum');
