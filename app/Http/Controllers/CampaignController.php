<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Http\Request;
use App\Http\Resources\CampaignResource;

class CampaignController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('permission:campaigns.read')->only(['index', 'show']);
        $this->middleware('permission:campaigns.write')->only(['store', 'update']);
        $this->middleware('permission:campaigns.delete')->only(['destroy']);
    }

     /**
     * Get all campaigns
     * @OA\Get(
     * path="/campaigns",
     * description="Get all campaigns",
     * tags={"Campaigns"},
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
     * @OA\Parameter(
     *    in="query",
     *    name="q",
     *    required=false,
     *    @OA\Schema(type="string"),
     * ),
     *     @OA\Parameter(
     *         in="query",
     *         name="user_id",
     *         required=false,
     *         description="Filter by user ID",
     *         @OA\Schema(type="integer"),
     *     ),
     *     @OA\Parameter(
     *         in="query",
     *         name="category_id",
     *         required=false,
     *         description="Filter by category ID",
     *         @OA\Schema(type="integer"),
     *     ),
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
            'user_id' => ['integer','exists:users,id'],
            'category_id' => ['integer','exists:campaign_categories,id'],
        ]);

        $q = Campaign::query();

        if ($request->q) {
            $searchTerm = $request->q;
            $q->search($searchTerm);
        }

        if($request->user_id){
            $q->where('user_id',$request->user_id);
        }

        if($request->category_id){
            $q->where('category_id',$request->category_id);
        }

        if ($request->with_paginate === '0')
            $campaigns = $q->with(['updates','category'])->get();
        else
            $campaigns = $q->with(['updates','category'])->paginate($request->per_page ?? 10);

        return CampaignResource::collection($campaigns);
    }

     /**
     * Get specific campaigns
     * @OA\Get(
     * path="/campaigns/{campaign}",
     * description="Get specific campaigns",
     *     @OA\Parameter(
     *         in="path",
     *         name="campaign",
     *         required=true,
     *         @OA\Schema(type="string"),
     *      ),
     * tags={"Campaigns"},
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

     public function show(Campaign $campaign)
     {
        $campaign->load(['updates','category']);
        return response()->json(new CampaignResource($campaign));
     }

        /**
      * Add new campaign.
     * @OA\Post(
     * path="/campaigns",
     * description="Add new campaign.",
     * tags={"Campaigns"},
     * security={{"bearer_token": {} }},
     *   @OA\RequestBody(
     *       required=true,
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(
     *                 required={"title", "description","category_id","target_amount","collected_amount", "status"},
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="category_id", type="integer"),
     *                 @OA\Property(property="target_amount", type="number", format="float"),
     *                 @OA\Property(property="collected_amount", type="number", format="float"),
     *                 @OA\Property(property="status", type="string"),
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

    public function store(Request $request)
    {
        $request->validate([
            'title' => ['required','string'],
            'description' => ['required','string'],
            'category_id' => ['required','exists:campaign_categories,id'],
            'target_amount' => ['required','numeric','min:0'],
            'collected_amount' => ['required','numeric','min:0'],
            'status' => ['required','string'],
        ]);

        $campaign = new Campaign();
        $campaign->user_id = auth()->user()->id;
        $campaign->title = $request->title;
        $campaign->description = $request->description;
        $campaign->category_id = $request->category_id;
        $campaign->target_amount = $request->target_amount;
        $campaign->collected_amount = $request->collected_amount;
        $campaign->status = $request->status;
        $campaign->save();

        $campaign->load('category');

        return response()->json(new CampaignResource($campaign), 201);
    }

    /**
    * Update a campaign.
     * @OA\Post(
     * path="/campaigns/{campaign}",
     * description="Update a campaign.",
     *    @OA\Parameter(
     *     in="path",
     *     name="campaign",
     *     required=true,
     *     @OA\Schema(type="string"),
     *   ),
     * tags={"Campaigns"},
     * security={{"bearer_token": {} }},
     *   @OA\RequestBody(
     *       required=true,
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(
     *                 required={"update_text"},
     *                 @OA\Property(property="update_text", type="string"),
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

    public function update(Request $request, Campaign $campaign)
    {
        $request->validate([
            'update_text' => ['required','string'],
        ]);

        $campaign->updates()->create([
            'update_text' => $request->update_text,
        ]);

        $campaign->load(['updates','category']);

        return response()->json(new CampaignResource($campaign), 200);
    }

    /**
     * Delete entered campaign.
     * @OA\Delete(
     * path="/campaigns/{campaign}",
     * description="Delete entered campaign.",
     *     @OA\Parameter(
     *         in="path",
     *         name="campaign",
     *         required=true,
     *         @OA\Schema(type="string"),
     *      ),
     * tags={"Campaigns"},
     * security={{"bearer_token":{}}},
     * @OA\Response(
     *    response=204,
     *    description="No Content"
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
     * )
     *)
     */

     public function destroy(Campaign $campaign)
     {  
        $campaign->delete();
        return response()->json(null, 204);
     }

}
