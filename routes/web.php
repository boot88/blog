<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AlarmController;

Route::redirect('/', '/alarms'); // ✅ главная ведёт на список будильников

Route::get('/alarms/due', [AlarmController::class, 'due'])->name('alarms.due');

Route::resource('alarms', AlarmController::class)
    ->except(['show']);
