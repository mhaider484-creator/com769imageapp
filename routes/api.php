<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\CommentController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



Route::post('/register', [AuthController::class,'register']);
Route::post('/login', [AuthController::class,'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class,'me']);
    Route::post('/logout', [AuthController::class,'logout']);

    Route::get('/posts', [PostController::class,'index']);
    Route::post('/posts', [PostController::class,'store']);
    Route::get('/posts/{post}', [PostController::class,'show']);
    Route::put('/posts/{post}', [PostController::class,'update']);
    Route::delete('/posts/{post}', [PostController::class,'destroy']);
    Route::post('/posts/{post}/like', [PostController::class,'toggleLike']);

    Route::get('/posts/{post}/comments', [CommentController::class,'index']);
    Route::post('/posts/{post}/comment', [CommentController::class,'store']);
});

