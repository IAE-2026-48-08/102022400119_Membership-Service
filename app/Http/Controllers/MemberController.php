<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMemberRequest;
use App\Http\Requests\VerifyMemberRequest;
use App\Models\Member;
use App\Services\MembershipService;
use Illuminate\Http\JsonResponse;

class MemberController extends Controller
{
    public function __construct(protected MembershipService $membershipService) {}

    /**
     * GET /api/v1/members
     * Admin melihat seluruh data member.
     */
    public function index(): JsonResponse
    {
        $members = Member::orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar seluruh member berhasil diambil.',
            'data'    => $members,
        ]);
    }

    /**
     * GET /api/v1/members/{id}
     * Ambil detail & status membership (dipanggil Service B untuk cek diskon).
     */
    public function show(int $id): JsonResponse
    {
        $member = Member::find($id);

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Member tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail member berhasil diambil.',
            'data'    => [
                'member'    => $member,
                'is_active' => $member->isActive(),
            ],
        ]);
    }

    /**
     * POST /api/v1/members
     * Daftarkan member baru. Nomor member & diskon di-generate otomatis.
     */
    public function store(StoreMemberRequest $request): JsonResponse
    {
        $memberType       = $request->membership_type;
        $discountPercent  = $this->membershipService->getDiscountByType($memberType);
        $memberNumber     = $this->membershipService->generateMemberNumber();

        $member = Member::create([
            'member_number'       => $memberNumber,
            'name'                => $request->name,
            'email'               => $request->email,
            'phone'               => $request->phone,
            'vehicle_plate'       => strtoupper($request->vehicle_plate),
            'membership_type'     => $memberType,
            'discount_percentage' => $discountPercent,
            'status'              => 'active',
            'joined_at'           => now(),
            'expired_at'          => $request->expired_at ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Member baru berhasil didaftarkan dengan nomor {$memberNumber}. Diskon {$discountPercent}% akan diterapkan otomatis.",
            'data'    => $member,
        ], 201);
    }

    /**
     * POST /api/v1/members/verification
     * Verifikasi membership pengguna saat transaksi parkir berlangsung (dipanggil Service B).
     * Input: vehicle_plate, subtotal (opsional untuk hitung langsung)
     */
    public function verify(VerifyMemberRequest $request): JsonResponse
    {
        $result = $this->membershipService->verifyMembership($request->vehicle_plate);

        if (!$result['valid']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'data'    => [
                    'vehicle_plate'       => $request->vehicle_plate,
                    'is_member'           => false,
                    'discount_percentage' => 0,
                ],
            ], 200); // tetap 200 agar Service B tidak error, tapi is_member=false
        }

        $responseData = [
            'vehicle_plate'       => $request->vehicle_plate,
            'is_member'           => true,
            'member_id'           => $result['member']->id,
            'member_number'       => $result['member']->member_number,
            'member_name'         => $result['member']->name,
            'membership_type'     => $result['member']->membership_type,
            'discount_percentage' => $result['discount_percentage'],
        ];

        // Jika subtotal dikirim, hitung langsung potongannya
        if ($request->filled('subtotal')) {
            $calc = $this->membershipService->applyMembershipDiscount(
                (float) $request->subtotal,
                $result['discount_percentage']
            );
            $responseData['calculation'] = $calc;
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data'    => $responseData,
        ]);
    }
}
