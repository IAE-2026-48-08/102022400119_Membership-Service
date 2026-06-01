<?php

use App\Http\Controllers\MemberController;
use App\Http\Controllers\VoucherController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Service C — Membership & Voucher API Routes
| DPark Bandung — Parking Management System
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ─── Member Routes ────────────────────────────────────────────────────
    Route::prefix('members')->group(function () {

        // GET  /api/v1/members          → Admin: lihat semua member
        Route::get('/', [MemberController::class, 'index']);

        // POST /api/v1/members/verification → Service B: verifikasi membership saat transaksi
        Route::post('/verification', [MemberController::class, 'verify']);

        // POST /api/v1/members          → Daftarkan member baru
        Route::post('/', [MemberController::class, 'store']);

        // GET  /api/v1/members/{id}     → Detail member & status membership
        Route::get('/{id}', [MemberController::class, 'show']);
    });

    // ─── Voucher Routes ───────────────────────────────────────────────────
    Route::prefix('vouchers')->group(function () {

        // GET  /api/v1/vouchers         → Daftar voucher tersedia
        Route::get('/', [VoucherController::class, 'index']);

        // GET  /api/v1/vouchers/my?member_id= → Voucher milik member
        Route::get('/my', [VoucherController::class, 'myVouchers']);

        // POST /api/v1/vouchers/validate → Service B: validasi voucher saat transaksi
        Route::post('/validate', [VoucherController::class, 'validate']);

        // PUT  /api/v1/vouchers/use     → Service B: tandai voucher terpakai
        Route::put('/use', [VoucherController::class, 'markUsed']);

        // POST /api/v1/vouchers/{id}/claim → Member klaim voucher
        Route::post('/{id}/claim', [VoucherController::class, 'claim']);
    });
});
