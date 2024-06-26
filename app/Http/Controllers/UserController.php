<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('permission:users.read')->only(['index', 'show']);
        $this->middleware('permission:users.write')->only(['store', 'update']);
        $this->middleware('permission:users.delete')->only(['destroy']);
    }

     /**
      * Get all users
     * @OA\Get(
     * path="/users",
     * description="Get all users",
     * tags={"Users"},
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
     *         name="role_id",
     *         required=false,
     *         description="Filter users by role ID",
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
            'role_id' => ['integer', 'exists:roles,id'],
        ]);

        $q = User::query();

        if ($request->role_id) {
            $role_id = $request->role_id;
            $q->whereHas('roles', function ($query) use ($role_id) {
                $query->where('roles.id', $role_id);
            });
        }

        if ($request->q) {
            $searchTerm = $request->q;
            $q->search($searchTerm);
        }

        if ($request->with_paginate === '0')
            $users = $q->with('roles')->get();
        else
            $users = $q->with('roles')->paginate($request->per_page ?? 10);

        return UserResource::collection($users);
    }

    /**
     * Get specific user
     * @OA\Get(
     * path="/users/{user}",
     * description="Get specific user",
     *     @OA\Parameter(
     *         in="path",
     *         name="user",
     *         required=true,
     *         @OA\Schema(type="string"),
     *      ),
     * operationId="show_users",
     * tags={"Users"},
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

     public function show(User $user)
     {
        $user->load('roles');
        return response()->json(new UserResource($user));
     }

     /**
      * Add new user.
     * @OA\Post(
     * path="/users",
     * description="Add new user.",
     * tags={"Users"},
     * security={{"bearer_token": {} }},
     *   @OA\RequestBody(
     *       required=true,
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(
     *                 required={"username","email", "password","first_name","last_name","profile_picture", "role_id"},
     *                 @OA\Property(property="username", type="string"),
     *                 @OA\Property(property="email", type="string"),
     *                 @OA\Property(property="password", type="string"),
     *                 @OA\Property(property="first_name", type="string"),
     *                 @OA\Property(property="last_name", type="string"),
     *                 @OA\Property(property="profile_picture", type="file", description="image file (JPEG/JPG/PNG)"),
     *                 @OA\Property(property="role_id", type="integer"),
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
        $Validator1 = Validator::make($request->all(), [
            'username' => ['required', Rule::unique('users', 'username')],
            'email' => ['required','email', Rule::unique('users', 'email')],
            'password' => ['required', 'string'],
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'profile_picture'=> ['required'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ]);

        $errors = $Validator1->errors();

        $Validator2 = Validator::make($request->all(), [
            'password' => [Password::min(8)->letters()->numbers()],
            'profile_picture' => ['image', 'mimes:jpeg,jpg,png'],
        ]);

        if ($Validator2->fails())
            if ($Validator2->errors()->has('profile_picture')) {
                $errors->add('profile_picture', __('validation.custom.profile_picture.custom_image', ['attribute' => __('validation.attributes.profile_picture')]));
            }
            if ($Validator2->errors()->has('password')) {
                $errors->add('password', __('validation.custom.password.custom_password', ['attribute' => __('validation.attributes.password')]));
            }

        if ($Validator1->fails() || $Validator2->fails())
            throw ValidationException::withMessages($errors->toArray());

        $user = new User();
        $user->username = $request->username;
        $user->email = $request->email;
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;

        $user->password = Hash::make($request->password);

        if ($request->hasFile('profile_picture')) {
            $imagePath = upload_file($request->file('profile_picture'), 'profile', 'images');
            $user->profile_picture = $imagePath;
        }

        $user->save();

        $role = Role::findOrFail($request->role_id);
        $user->assignRole($role);
        $user->load('roles');

        return response()->json(new UserResource($user), 201);
    }

    /**
     * Edit a user.
     * @OA\Post(
     * path="/users/{user}",
     * description="Edit a user.",
     *    @OA\Parameter(
     *     in="path",
     *     name="user",
     *     required=true,
     *     @OA\Schema(type="string"),
     *   ),
     * tags={"Users"},
     * security={{"bearer_token": {} }},
     *   @OA\RequestBody(
     *       required=true,
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(
     *                 required={"username","email", "password","first_name","last_name","profile_picture","role_id"},
     *                 @OA\Property(property="username", type="string"),
     *                 @OA\Property(property="email", type="string"),
     *                 @OA\Property(property="password", type="string"),
     *                 @OA\Property(property="first_name", type="string"),
     *                 @OA\Property(property="last_name", type="string"),
     *                 @OA\Property(property="profile_picture", type="file", description="image file (JPEG/JPG/PNG)"),
     *                 @OA\Property(property="role_id", type="integer"),
     *                 @OA\Property(property="_method", type="string", format="string", example="PUT"),
     *          )
     *       )
     *   ),
     * @OA\Response(
     *    response=200,
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

    public function update(Request $request, User $user)
    {
        $Validator1 = Validator::make($request->all(), [
            'username' => ['required', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['required','email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['required', 'string'],
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'profile_picture'=> ['required'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ]);

        $errors = $Validator1->errors();

        $Validator2 = Validator::make($request->all(), [
            'password' => [Password::min(8)->letters()->numbers()],
            'profile_picture' => ['image', 'mimes:jpeg,jpg,png'],
        ]);

        if ($Validator2->fails())
            if ($Validator2->errors()->has('profile_picture')) {
                $errors->add('profile_picture', __('validation.custom.profile_picture.custom_image', ['attribute' => __('validation.attributes.profile_picture')]));
            }
            if ($Validator2->errors()->has('password')) {
                $errors->add('password', __('validation.custom.password.custom_password', ['attribute' => __('validation.attributes.password')]));
            }

        if ($Validator1->fails() || $Validator2->fails())
            throw ValidationException::withMessages($errors->toArray());

        $user->username = $request->username;
        $user->email = $request->email;
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;

        $user->password = Hash::make($request->password);

        if ($request->hasFile('profile_picture')) {
            if (!is_null($user->profile_picture)) {
                delete_file_if_exist($user->profile_picture);
            }

            $imagePath = upload_file($request->file('profile_picture'), 'profile', 'images');
            $user->profile_picture = $imagePath;
        }

        $user->save();

        $role = Role::findOrFail($request->role_id);
        $user->syncRoles([$role]);
        $user->load('roles');

        return response()->json(new UserResource($user), 200);
    }

    /**
     * Delete entered user.
     * @OA\Delete(
     * path="/users/{user}",
     * description="Delete entered user.",
     *     @OA\Parameter(
     *         in="path",
     *         name="user",
     *         required=true,
     *         @OA\Schema(type="string"),
     *      ),
     * operationId="delete_user",
     * tags={"Users"},
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

     public function destroy(User $user)
     {
        if (!is_null($user->profile_picture)) {
            delete_file_if_exist($user->profile_picture);
        }
        
        $user->delete();
        return response()->json(null, 204);
     }
}
