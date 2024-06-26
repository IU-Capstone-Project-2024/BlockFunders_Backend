<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\CampaignCategory;
use App\Http\Resources\CampaignCategoryResource;

class CampaignCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('permission:campaign_categories.read')->only(['index', 'show']);
        $this->middleware('permission:campaign_categories.write')->only(['store', 'update']);
        $this->middleware('permission:campaign_categories.delete')->only(['destroy']);
    }

       /**
     * Get all campaign categories
     * @OA\Get(
     * path="/campaign/categories",
     * description="Get all campaign categories",
     * tags={"Campaigns Categories"},
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

        $q = CampaignCategory::query();

        if ($request->q) {
            $searchTerm = $request->q;
            $q->search($searchTerm);
        }

        if ($request->with_paginate === '0')
            $campaign_categories = $q->get();
        else
            $campaign_categories = $q->paginate($request->per_page ?? 10);

        return CampaignCategoryResource::collection($campaign_categories);
    }

    
     /**
     * Get specific campaign category
     * @OA\Get(
     * path="/campaign/categories/{category}",
     * description="Get specific campaign category",
     *     @OA\Parameter(
     *         in="path",
     *         name="category",
     *         required=true,
     *         @OA\Schema(type="string"),
     *      ),
     * tags={"Campaigns Categories"},
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

    public function show(CampaignCategory $category)
    {
       return response()->json(new CampaignCategoryResource($category),200);
    }

        /**
      * Add new campaign category.
     * @OA\Post(
     * path="/campaign/categories",
     * description="Add new campaign category.",
     * tags={"Campaigns Categories"},
     * security={{"bearer_token": {} }},
     *   @OA\RequestBody(
     *       required=true,
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(
     *                 required={"category_name"},
     *                 @OA\Property(property="category_name", type="string"),
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
            'category_name' => ['required','string' , Rule::unique('campaign_categories', 'category_name')],
        ]);

        $campaign_category = new CampaignCategory();
        $campaign_category->category_name = $request->category_name;
        $campaign_category->save();

        return response()->json(new CampaignCategoryResource($campaign_category), 201);
    }

     /**
    * Update a campaign category.
     * @OA\Post(
     * path="/campaign/categories/{category}",
     * description="Update a campaign category.",
     *    @OA\Parameter(
     *     in="path",
     *     name="category",
     *     required=true,
     *     @OA\Schema(type="string"),
     *   ),
     * tags={"Campaigns Categories"},
     * security={{"bearer_token": {} }},
     *   @OA\RequestBody(
     *       required=true,
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(
     *                 required={"category_name"},
     *                 @OA\Property(property="category_name", type="string"),
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

    public function update(Request $request, CampaignCategory $category)
    {
        $request->validate([
            'category_name' => ['required','string', Rule::unique('campaign_categories', 'category_name')->ignore($category->id)],
        ]);

        $category->category_name = $request->category_name;
        $category->save();

        return response()->json(new CampaignCategoryResource($category), 200);
    }

    /**
     * Delete entered campaign category.
     * @OA\Delete(
     * path="/campaign/categories/{category}",
     * description="Delete entered campaign category.",
     *     @OA\Parameter(
     *         in="path",
     *         name="category",
     *         required=true,
     *         @OA\Schema(type="string"),
     *      ),
     * tags={"Campaigns Categories"},
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

    public function destroy(CampaignCategory $category)
    {  
       $category->delete();
       return response()->json(null, 204);
    }
}
