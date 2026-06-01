<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClaimVoucherRequest;
use App\Http\Requests\ValidateVoucherRequest;
use App\Models\MemberVoucher;
use App\Models\Voucher;
use App\Services\VoucherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function __construct(protected VoucherService $voucherService) {}

    /**
     * GET /api/v1/vouchers
     * Lihat daftar voucher yang tersedia (aktif, belum expired, masih ada kuota).
     */
    public function index(): JsonResponse
    {
        $vouchers = Voucher::where('is_active', true)
                           ->where('valid_until', '>=', now()->toDateString())
                           ->whereColumn('claimed_count', '<', 'total_quota')
                           ->orderBy('valid_until')
                           ->get()
                           ->map(function ($v) {
                               $v->remaining_quota = $v->total_quota - $v->claimed_count;
                               return $v;
                           });

        return response()->json([
            'success' => true,
            'message' => 'Daftar voucher tersedia berhasil diambil.',
            'data'    => $vouchers,
        ]);
    }

    /**
     * POST /api/v1/vouchers/{id}/claim
     * Member mengklaim voucher.
     */
    public function claim(ClaimVoucherRequest $request, int $id): JsonResponse
    {
        $result = $this->voucherService->claimVoucher($request->member_id, $id);

        $statusCode = $result['success'] ? 201 : 422;

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data'    => $result['data'],
        ], $statusCode);
    }

    /**
     * GET /api/v1/vouchers/my?member_id={id}
     * Member melihat voucher yang sudah diklaim beserta statusnya.
     */
    public function myVouchers(Request $request): JsonResponse
    {
        $request->validate([
            'member_id' => 'required|integer|exists:members,id',
        ]);

        $memberVouchers = MemberVoucher::with('voucher')
                                        ->where('member_id', $request->member_id)
                                        ->orderBy('claimed_at', 'desc')
                                        ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar voucher member berhasil diambil.',
            'data'    => $memberVouchers,
        ]);
    }

    /**
     * POST /api/v1/vouchers/validate
     * Validasi voucher saat transaksi berlangsung (dipanggil Service B).
     * Input: voucher_code, member_id, subtotal, duration_hours
     */
    public function validate(ValidateVoucherRequest $request): JsonResponse
    {
        $result = $this->voucherService->validateVoucher(
            $request->voucher_code,
            $request->member_id,
            (float) $request->subtotal,
            (float) $request->duration_hours
        );

        if (!$result['valid']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'data'    => [
                    'voucher_code'    => $request->voucher_code,
                    'is_valid'        => false,
                    'discount_amount' => 0,
                ],
            ], 200);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data'    => [
                'voucher_code'      => $request->voucher_code,
                'is_valid'          => true,
                'discount_type'     => $result['discount_type'],
                'discount_value'    => $result['discount_value'],
                'discount_amount'   => $result['discount_amount'],
                'final_amount'      => max(0, (float) $request->subtotal - $result['discount_amount']),
                'member_voucher_id' => $result['member_voucher_id'],
            ],
        ]);
    }

    /**
     * PUT /api/v1/vouchers/use
     * Tandai voucher sebagai terpakai setelah transaksi selesai (dipanggil Service B).
     * Input: member_voucher_id, transaction_id
     */
    public function markUsed(Request $request): JsonResponse
    {
        $request->validate([
            'member_voucher_id' => 'required|integer|exists:member_vouchers,id',
            'transaction_id'    => 'required|string',
        ]);

        $success = $this->voucherService->markVoucherAsUsed(
            $request->member_voucher_id,
            $request->transaction_id
        );

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Voucher berhasil ditandai sebagai terpakai.' : 'Gagal menandai voucher.',
        ]);
    }
}
