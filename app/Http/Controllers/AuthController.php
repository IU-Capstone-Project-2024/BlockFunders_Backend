<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->only(['logout', 'profile']);
    }


    function get_random_profile_image() {
        // give random number in php laravel
        $random_number = rand(1, 3);
        return url('public/profile/' .$random_number . '.png');
    }


     /**
     * @OA\Post(
     * path="/register",
     * description="Register a new user",
     * tags={"Auth"},
     * security={{"bearer_token": {} }},
     *   @OA\RequestBody(
     *       required=true,
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(
     *              required={"username", "email", "password", "password_confirmation","first_name","last_name"},
     *              @OA\Property(property="username", type="string"),
     *              @OA\Property(property="email", type="string", format="email"),
     *              @OA\Property(property="first_name", type="string"),
     *              @OA\Property(property="last_name", type="string"),
     *              @OA\Property(property="password", type="string", minLength=8 , example="12345678a"),
     *              @OA\Property(property="password_confirmation", type="string", minLength=8, example="12345678a"),
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
     *         description="Validation Error."
     *     )
     * )
     * )
     */
    public function register(Request $request)
    {
        $Validator1 = Validator::make($request->all(),[
            'username' => ['required', Rule::unique('users', 'username')],
            'email' => ['required','email', Rule::unique('users', 'email')],
            'password'  => ['required', 'confirmed', 'string'],
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
        ]);

        $errors = $Validator1->errors();
        $Validator2 = Validator::make($request->all(),[
            'password' => [Password::min(8)->letters()->numbers()],
        ]);

        if($Validator2->fails())
        $errors->add('password', __('validation.custom.password.custom_password', ['attribute' => __('validation.attributes.password')]));

        if($Validator1->fails() || $Validator2->fails())
            throw ValidationException::withMessages($errors->toArray());

        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'password' => Hash::make($request->password),
            'profile_picture' => $this->get_random_profile_image(),
        ]);

        $token = $user->createToken('block-funders')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ], 200);
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

     /**
     * Get My Profile
     * @OA\Post(
     * path="/profile",
     * description="user profile",
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

     public function profile(Request $request)
     {
        $request->user()->load('transactions');
        return response()->json(new UserResource($request->user()));
     }
}
