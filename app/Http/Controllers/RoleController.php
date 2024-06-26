<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use App\Http\Resources\RoleResource;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('permission:roles.read')->only(['index','show']);
        $this->middleware('permission:roles.write')->only(['store','update']);
        $this->middleware('permission:roles.delete')->only(['destroy']);
    }

      /**
     * @OA\Get(
     * path="/roles",
     * description="get all roles",
     * tags={"Roles"},
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
     *    name="search",
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

        $searchTerm = $request->query('search');
        $query = Role::query();
    
        if ($searchTerm) {
            $query->where('name', 'like', '%' . $searchTerm . '%');
        }

        if ($request->with_paginate === '0')
            $roles = $query->with('permissions')->get();
        else
            $roles = $query->with('permissions')->paginate($request->per_page ?? 10);
        
        return RoleResource::collection($roles);
    }

    /**
     * @OA\Post(
     *     path="/roles",
     *     description="Create a new role",
     *     tags={"Roles"},
     *     security={{"bearer_token": {} }},
     *   @OA\RequestBody(
     *       required=true,
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(
     *              required={"name"},
     *                 @OA\Property(property="name", type="string"),
     *          )
     *       )
     *   ),
     *     @OA\Response(
     *         response=201,
     *         description="Created",
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *     )
     * )
     */

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', Rule::unique('roles','name')],
        ]);

        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'web'
        ]);

        return response()->json(new RoleResource($role),201);
    }

        /**
     * @OA\Post(
     * path="/roles/{role}",
     * description="Edit specific role",
     *   @OA\Parameter(
     *     in="path",
     *     name="role",
     *     required=true,
     *     @OA\Schema(type="string"),
     *   ),
     *  tags={"Roles"},
     *   @OA\RequestBody(
     *       required=true,
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(
     *              required={"name"},
     *              @OA\Property(property="name", type="string"),
     *              @OA\Property(property="_method", type="string", format="string", example="PUT"),
     *           )
     *       )
     *   ),
     * security={{"bearer_token":{}}},
     *     @OA\Response(
     *         response="200",
     *    description="Success"
     *     ),
     *   @OA\Response(
     *     response=401,
     *     description="Unauthenticated",
     *  ),
     *   @OA\Response(
     *     response=422,
     *     description="Validation error",
     *  ),
     *   @OA\Response(
     *     response=403,
     *     description="Forbidden. This action is unauthorized.",
     *  )
     * )
     */

    public function update(Request $request, Role $role)
    {
        if ($role->name == 'admin' || $role->name == 'user')
            throw new BadRequestHttpException(__('api.cannot_modify'));
        
        $request->validate([
            'name' => ['required', Rule::unique('roles','name')->ignore($role->id)],
        ]);

        $role->update([
            'name' => $request->name,
            'guard_name' => 'web'
        ]);

        return response()->json(new RoleResource($role),200);
    }

    /**
     * @OA\Get(
     *     path="/roles/{role}",
     *     description="Get a role by ID",
     *     tags={"Roles"},
     *     security={{"bearer_token": {} }},
     *     @OA\Parameter(
     *         name="role",
     *         in="path",
     *         description="Role ID",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Role not found",
     *     )
     * )
     */

    public function show(Role $role)
    {
        $role->load('permissions');
        return response()->json(new RoleResource($role),200);
    }

    /**
     * @OA\Delete(
     *     path="/roles/{role}",
     *     description="Delete a role by ID",
     *     tags={"Roles"},
     *     security={{"bearer_token": {} }},
     *     @OA\Parameter(
     *         name="role",
     *         in="path",
     *         description="Role ID",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="No Content",
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Role not found",
     *     )
     * )
     */

    public function destroy(Role $role)
    {
        if ($role->name == 'admin' || $role->name == 'user')
            throw new BadRequestHttpException(__('api.cannot_modify'));

        $role->delete();

        return response()->json(null, 204);
    }
}
