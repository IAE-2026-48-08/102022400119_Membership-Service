<?php

namespace App\Http\Controllers;

use App\Http\Requests\VerifyMemberRequest;
use App\Models\Member;
use App\Services\MembershipService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class MemberController extends Controller
{
    public function __construct(protected MembershipService $membershipService) {}

    #[OA\Get(
        path: "/members",
        summary: "Get list of all members",
        security: [["ApiKeyAuth" => []]],
        tags: ["Member"]
    )]
    #[OA\Response(response: 200, description: "Successful operation")]
    public function index(): JsonResponse
    {
        $members = Member::orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar seluruh member berhasil diambil.',
            'data'    => $members,
        ]);
    }

    #[OA\Get(
        path: "/members/{id}",
        summary: "Get member details and membership status",
        security: [["ApiKeyAuth" => []]],
        tags: ["Member"]
    )]
    #[OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))]
    #[OA\Response(response: 200, description: "Successful operation")]
    #[OA\Response(response: 404, description: "Member not found")]
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

    #[OA\Post(
        path: "/members/verification",
        summary: "Verify membership during parking transaction",
        security: [["ApiKeyAuth" => []]],
        tags: ["Member"]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "vehicle_plate", type: "string", example: "D 1234 ABC"),
                new OA\Property(property: "subtotal", type: "number", nullable: true, example: 10000)
            ]
        )
    )]
    #[OA\Response(response: 200, description: "Verification result")]
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
            ], 200);
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
