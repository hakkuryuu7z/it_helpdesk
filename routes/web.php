<?php

use App\Http\Controllers\BranchController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Master Data Management
    Route::resource('branches', BranchController::class);
    Route::resource('departments', DepartmentController::class)->except(['show']);
    Route::resource('users', UserController::class);

    // Ticket Management
    Route::resource('tickets', \App\Http\Controllers\TicketController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('tickets/{ticket}/assign', [\App\Http\Controllers\TicketController::class, 'assign'])->name('tickets.assign');
    Route::patch('tickets/{ticket}/status', [\App\Http\Controllers\TicketController::class, 'updateStatus'])->name('tickets.status');
    Route::post('tickets/{ticket}/comments', [\App\Http\Controllers\TicketController::class, 'addComment'])->name('tickets.comments.store');

    // Secure File Downloads
    Route::get('attachments/{attachment}/download', [\App\Http\Controllers\TicketAttachmentController::class, 'download'])->name('attachments.download');
});

require __DIR__.'/auth.php';
