<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NexusController;
use App\Http\Controllers\AuthController;

// Auth
Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected routes - Admin only
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', [NexusController::class, 'dashboard']);
    Route::get('/pps', [NexusController::class, 'pps']);
});

// Protected routes - Admin + Employee
Route::middleware(['auth', 'role:admin,employee'])->group(function () {
    Route::get('/employees', [NexusController::class, 'employees']);
    Route::get('/employees/{matricule}', [NexusController::class, 'employeeDetail']);
});
