<?php

use App\Http\Controllers\AlarmController;
use App\Http\Controllers\OrganizerItemController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/alarms');

Route::get('/alarms/due', [AlarmController::class, 'due'])->name('alarms.due');
Route::patch('/alarms/{alarm}/enabled', [AlarmController::class, 'toggleEnabled'])->name('alarms.toggle-enabled');
Route::resource('alarms', AlarmController::class)->except(['show']);

Route::post('/items/tasks/import-local', [OrganizerItemController::class, 'importLocalTasks'])
    ->name('items.import-local');
Route::get('/items/{section}', [OrganizerItemController::class, 'index'])->name('items.index');
Route::post('/items/{section}', [OrganizerItemController::class, 'store'])->name('items.store');
Route::get('/items/{section}/{item}/edit', [OrganizerItemController::class, 'edit'])->name('items.edit');
Route::put('/items/{section}/{item}', [OrganizerItemController::class, 'update'])->name('items.update');
Route::delete('/items/{section}/{item}', [OrganizerItemController::class, 'destroy'])->name('items.destroy');
