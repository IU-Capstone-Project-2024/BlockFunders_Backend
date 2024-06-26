<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->only(['logout']);
    }

    /**
     * Login a user with (username OR email) And password
     * @OA\Post(
     * path="/login",
     * description="login a user",
     * tags={"Auth"},
     *   @OA\RequestBody(
     *       required=true,
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(
     *              required={"password"},
     *              @OA\Property(property="username", type="string"),
     *              @OA\Property(property="email", type="string"),
     *              @OA\Property(property="password", type="string", minLength=8 , example="12345678a"),
     * 
     *          )
     *       )
     *   ),
     * @OA\Response(
     *    response=200,
     *    description="successful operation",
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error."
     *     ),
     * )
     * )
     */

    public function login(Request $request)
    {
        $request->validate([
            'email' => ['nullable', 'email', 'string', 'required_without:username'],
            'username' => ['nullable', 'string', 'required_without:email'],    
            'password' => ['required', 'string'],
        ]);

        $credentials = $request->only('password');

        if ($request->filled('email')) {
            $credentials['email'] = $request->email;
        } else {
            $credentials['username'] = $request->username;
        }

        if (auth()->attempt($credentials)) {
            $user = to_user(auth()->user());
            $user->load('roles');

            $token = $request->user()->createToken('block-funders')->plainTextToken;

            return response()->json([
                'user' => new UserResource($user),
                'token' => $token,
            ], 200);
        } else {
            throw ValidationException::withMessages([
                'wrong_credentials' => __('api.wrong_credentials')
            ]);
        }
    }

    /**
     * Logout a user
     * @OA\Post(
     * path="/logout",
     * description="logout a user",
     * tags={"Auth"},
     * security={{"bearer_token": {} }},
     * @OA\Response(
     *    response=200,
     *    description="successful operation",
     *     ),
     *  @OA\Response(
     *    response=401,
     *    description="Unauthenticated",
     *     )
     * )
     * )
     */

     public function logout(Request $request)
     {
        $request->user()->tokens()->delete();
     }
}
