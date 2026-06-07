<?php

use App\Http\Controllers\MemberController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Service C — Membership API Routes
| DPark Bandung — Parking Management System
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ─── Member Routes ────────────────────────────────────────────────────

    Route::prefix('members')->group(function () {

        // GET  /api/v1/members          → Admin: lihat semua member
        Route::get('/', [MemberController::class, 'index']);

        // POST /api/v1/members/verification → Verifikasi membership saat transaksi
        Route::post('/verification', [MemberController::class, 'verify']);

        // GET  /api/v1/members/{id}     → Detail member & status membership
        Route::get('/{id}', [MemberController::class, 'show']);
    });
});
