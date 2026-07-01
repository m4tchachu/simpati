<?php

use App\Http\Controllers\Web\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home']);
Route::get('/login', [PageController::class, 'login'])->name('login');
Route::get('/dashboard', [PageController::class, 'dashboard'])->name('dashboard');
Route::get('/debts', [PageController::class, 'debtsIndex'])->name('debts.index');
Route::get('/debts/new', [PageController::class, 'debtsCreate'])->name('debts.create');
Route::get('/debts/{id}', [PageController::class, 'debtsShow'])->name('debts.show');
Route::get('/notifications', [PageController::class, 'notifications'])->name('notifications');
Route::get('/students', [PageController::class, 'students'])->name('students');
Route::get('/students/new', [PageController::class, 'studentsCreate'])->name('students.create');
Route::get('/profile', [PageController::class, 'profile'])->name('profile');
