<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\CampaignCategoryController;
use App\Http\Controllers\ClaimController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);
Route::post('/profile', [AuthController::class, 'profile']);
Route::apiResource('/users', UserController::class);
Route::get('/permissions', [PermissionController::class, 'get_all_permissions']);
Route::get('/permissions/me', [PermissionController::class, 'my_permissions']);
Route::apiResource('roles', RoleController::class);
Route::get('/roles/{role}/permissions', [PermissionController::class, 'get_permissions']);
Route::post('/roles/{role}/permissions', [PermissionController::class, 'set_permissions']);
Route::post('/campaigns/{campaign}/fund', [CampaignController::class, 'fund']);
Route::apiResource('campaigns', CampaignController::class);
Route::apiResource('claims', ClaimController::class);
Route::apiResource('campaign/categories', CampaignCategoryController::class);
