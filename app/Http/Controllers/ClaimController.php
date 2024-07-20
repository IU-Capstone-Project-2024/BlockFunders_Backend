<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use Illuminate\Http\Request;

class ClaimController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:sanctum');

    }

    /**
     * Get all my claims
     * @OA\Get(
     * path="/claims",
     * description="Get all my claims",
     * tags={"Claims"},
     * security={{"bearer_token": {} }},
     *     @OA\Parameter(
     *         name="with_paginate",
     *         in="query",
     *         description="Enable pagination (0 = false, 1 = true)",
     *         required=false,
     *         @OA\Schema(
     *             type="integer",
     *             enum={0, 1}
     *         )
     *     ),
     * @OA\Parameter(
     *    in="query",
     *    name="per_page",
     *    required=false,
     *    @OA\Schema(type="integer"),
     * ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *  ),
     *   @OA\Response(
     *     response=401,
     *     description="Unauthenticated",
     *  ),
     *   @OA\Response(
     *     response=403,
     *     description="Forbidden. This action is unauthorized.",
     *  )
     *  )
     */
    public function index(Request $request)
    {
        $request->validate([
            'with_paginate' => ['integer', 'in:0,1'],
            'per_page' => ['integer', 'min:1'],
        ]);
        $user = to_user(auth()->user());

        $q = Claim::query();
        $q->where('user_id', $user->id);

        if ($request->with_paginate === '0')
            $claims = $q->get();
        else
            $claims = $q->paginate($request->per_page ?? 10);

        foreach ($claims as $claim) {
            $claim->metadata = json_decode($claim->metadata);
        }

        return response()->json($claims, 200);
    }


    /**
     * Get specific claim
     * @OA\Get(
     * path="/claims/{claim}",
     * description="Get specific claims",
     *     @OA\Parameter(
     *         in="path",
     *         name="claim",
     *         required=true,
     *         @OA\Schema(type="string"),
     *      ),
     * tags={"Claims"},
     * security={{"bearer_token": {} }},
     * @OA\Response(
     *    response=200,
     *    description="Success"
     * ),
     *   @OA\Response(
     *     response=401,
     *     description="Unauthenticated",
     *  ),
     *   @OA\Response(
     *     response=404,
     *     description="Model not found.",
     *  ),
     *   @OA\Response(
     *     response=403,
     *     description="Forbidden. This action is unauthorized.",
     *  )
     *)
     */

    public function show(Claim $claim)
    {
        $user = to_user(auth()->user());
        if ($user->id !== $claim->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return response()->json($claim, 200);
    }

    /**
    * Claim minted.
     * @OA\Post(
     * path="/claims/{claim}",
     * description="Mint a claim.",
     *    @OA\Parameter(
     *     in="path",
     *     name="claim",
     *     required=true,
     *     @OA\Schema(type="string"),
     *   ),
     * tags={"Claims"},
     * security={{"bearer_token": {} }},
     *   @OA\RequestBody(
     *       required=true,
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(
     *                 required={"tx_hash"},
     *                 @OA\Property(property="tx_hash", type="string"),
     *                 @OA\Property(property="_method", type="string", format="string", example="PUT"),

     *          )
     *       )
     *   ),
     * @OA\Response(
     *    response=201,
     *    description="successful operation",
     *     ),
     *   @OA\Response(
     *     response=401,
     *     description="Unauthenticated",
     *  ),
     *   @OA\Response(
     *     response=403,
     *     description="Forbidden. This action is unauthorized.",
     *  ),
     *   @OA\Response(
     *     response=422,
     *     description="Validation error.",
     *  )
     * )
     * )
     */
    public function update(Request $request, Claim $claim)
    {
        $user = to_user(auth()->user());
        if ($user->id !== $claim->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $request->validate([
            'tx_hash' => ['required', 'string', 'unique:claims,tx_hash'],
        ]);
        $claim->update([
            'tx_hash' => $request->tx_hash,
            'status' => 'claimed',
            'link' => 'https://sepolia.arbiscan.io/tx/' . $request->tx_hash,
        ]);
        return response()->json($claim, 200);
    }

}
