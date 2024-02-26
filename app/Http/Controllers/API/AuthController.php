<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Mail\ConfirmationCode;
use App\Models\EmailToVerify;
use App\Mail\ConfirmationLink;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Session;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Decoders\FilePathImageDecoder;
use Intervention\Image\Decoders\DataUriImageDecoder;
use Intervention\Image\Decoders\Base64ImageDecoder;
use Intervention\Image\Encoders\WebpEncoder;

class AuthController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // Rate limit the number of requests from any user to prevent server overload, with a maximum of 100 requests per minute.
        $this->middleware('throttle:api');

        // Apply 'verified' middleware to all methods except the specified ones.
        $this->middleware(['verified'], ['except' => ['login', 'logout', 'register', 'email_send_code', 'verify_code_email', 'verify_link_email','merge_dni_photos']]);

        // Apply 'auth:sanctum' middleware to all methods except the specified ones.
        $this->middleware(['auth:sanctum'], ['except' => ['login', 'register', 'verify_link_email', 'forgot_password', 'reset_password','merge_dni_photos']]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'cuil' => 'required|string',
            'password' => 'required|string'
        ]);

        if (Auth::attempt($request->only(['cuil', 'password']))) {
            $user = $request->user();
            $user->tokens()->delete();
            $token = $user->createToken('token-name')->plainTextToken;

            return response()->json([
                'user' => $user->only(['name', 'email', 'cuil']),
                'authorization' => [
                    'token' => $token,
                    'type' => 'Bearer ',
                    'X_CSRF_TOKEN' => csrf_token()
                ]
            ], Response::HTTP_CREATED);
        }

        return response()->json([
            'message' => 'Invalid credentials',
        ], Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Register a new user
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        // Validate the incoming request data
        $request->validate([
            'cuil' => [
                'required',
                'string',
                'unique:users',
                'regex:/^\d{2}-\d{8}-\d{1}$/',
            ],
            'firstname' => 'required|string|max:150',
            'lastname' => 'required|string|max:100',
            'birthdate' => 'required|date',
            'gender' => 'required', // Consider providing more specific validation
            'address' => 'required|string|max:100',
            'address_number' => 'required|integer',
            'floor' => 'nullable|string|max:5',
            'apartament' => 'nullable|string|max:5',
            'postalcode' => 'required|string|max:10',
            'city' => 'required|string|max:200',
            'province' => 'required|string|max:200',
            'phone' => 'required|string|max:200',
            'startdate' => 'required|date',
            'occupation' => 'nullable|string|max:4', // Consider providing more specific validation
            'study' => 'nullable|string|max:4',  // Consider providing more specific validation
            'email' => 'required|string|email|max:255|unique:users|unique:email_to_verify',
            'password' => 'required|string|min:8',
            'renewvaldate' => 'nullable|date'

        ]);

        // Create user data
        $data = [
            'name' => implode(', ', [$request->firstname, $request->lastname]),
            'cuil' => $request->cuil,
            'password' => Hash::make($request->password),
            'email' => $request->email,
        ];

        // Begin a database transaction
        DB::beginTransaction();
        try {
            // Create a new user
            $user = User::create($data);

            // Create a new email verification record
            $emailToVerify = new EmailToVerify([
                'email' => $request->email,
                'code' => self::str_random(10),
            ]);
            $user->emailToVerify()->save($emailToVerify);

            // Send the verification email
            Mail::to($request->email)->send(new ConfirmationLink($user->name, $emailToVerify->id, $emailToVerify->code));

            // Register an AE (whatever that stands for) - consider providing more information in the comment
            $ae = AeController::register_ae($request);

            // Commit the database transaction
            DB::commit();

            // Return a JSON response
            return response()->json([
                'message' => 'User created successfully',
                'user' => $user,
                'ae' =>  $ae->body()
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            // Rollback the database transaction and return an error response
            DB::rollback();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function merge_dni_photos($image_list){
        /*
        $photos = [$request->file('photo1'),$request->file('photo2')];
        $image_path= [];
        foreach($photos as $photo){
            $image_path[] = $photo->store('temp/images');
        }*/
        $manager = ImageManager::gd();
        $resized = [];
        foreach($image_list as $photo){
            $resized[] =  $manager->read($photo)->resize(1280, 720, function ($constraint) {
                $constraint->aspectRatio();
            });
        }
        $img_merged = $manager->create(1280, 1440);
        $img_merged->place($resized[0], 'top-left');
        $img_merged->place($resized[1], 'bottom-left');
        $image = $img_merged->encode(new WebpEncoder(quality: 75));
        return base64_encode($image);
    }

    public function logout()
    {
        if (Auth::check()) {
            //Auth::logout();
            Auth::user()->tokens()->delete();

            return response()->json([
                'message' => 'Successfully logged out',
            ], Response::HTTP_OK);
        }
        return response()->json([
            'message' => 'Unauthenticated',
        ], Response::HTTP_UNAUTHORIZED);
    }


    public function refresh()
    {
        // Sin uso actualmente
        if (Auth::check()) {
            $user = Auth::user();

            return response()->json([
                'user' => $user,
                'authorization' => [
                    'token' => Auth::refresh(),
                    'type' => 'bearer',
                    'expires_in' => Auth::factory()->getTTL() * 60, // Tiempo de expiración del nuevo token en segundos
                ],
            ], Response::HTTP_CREATED);
        }
        return response()->json([
            'message' => 'Unauthenticated',
        ], Response::HTTP_UNAUTHORIZED);
    }

    static private function str_random($length = 10)
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $randomString;
    }


    /**
     * Validate the request data and send a verification code to the provided email address.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function email_send_code(Request $request)
    {
        // Validate the request data
        $request->validate([
            'email' => 'required|string|email|max:255',
            'password' => 'required|string'
        ]);

        // Get the authenticated user
        $user = Auth::user();

        // Check if the user is not authenticated or the password is incorrect
        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Check if the email is already registered for another user
        $existingUser = User::where('email', $request->email)->where('id', '!=', $user->id)->first();

        if ($existingUser) {
            return response()->json([
                'message' => 'Email already registered',
            ], Response::HTTP_CONFLICT);
        }

        // Create or update the email verification code for the user
        $emailToVerify = $user->emailToVerify ?? new EmailToVerify;
        $emailToVerify->email = $request->email;
        $emailToVerify->code = self::str_random(10);
        $user->emailToVerify()->save($emailToVerify);

        // Send the verification code to the provided email address
        Mail::to($request->email)->send(new ConfirmationCode($emailToVerify->code, $user->name));

        // Return a success response
        return response()->json([
            'message' => 'Email sent',
        ], Response::HTTP_OK);
    }
    // verifica el email con el codigo obtenido.
    public function verify_code_email(Request $request)
    {
        $request->validate(['code' => 'required|regex:/^[A-Z0-9]{10}$/', 'email' => 'required|string|email|max:255']);
        $code = $request->code;
        if (Auth::check()) {
            $user = Auth::user();
            $emailToVerify = $user->emailToVerify;

            if (!$emailToVerify || $emailToVerify->code !== $request->code) {
                return response()->json(['error' => 'Invalid code'], Response::HTTP_BAD_REQUEST);
            }

            $expirationTime = now()->subMinutes(5);
            if ($emailToVerify->updated_at < $expirationTime) {
                return response()->json(['error' => 'Verification code has expired'], Response::HTTP_BAD_REQUEST);
            }

            if ($request->user()->markEmailAsVerified()) {
                $user->email = $emailToVerify->email;
                $emailToVerify->delete();
                $user->save();
                //event(new Verified($user));
                return response()->json(['message' => 'Confirmation successful'], Response::HTTP_OK);
            }else {
                return response()->json(['error' => 'Failed to mark email as verified'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

        } else {
            return response()->json(['error' => 'Unauthenticated'], Response::HTTP_UNAUTHORIZED);
        }
    }
    //esta verificacion hace lo mismo pero envia un link ( esta es para el registro )
    public function verify_link_email(Request $request)
    {
        $request->validate([
            'hash' => 'required|regex:/^[A-Z0-9]{10}$/',
            'id' => 'required|string|max:255']);

        $code = $request->query('hash');
        $id = $request->query('id');

        //$user = Auth::user();
        $emailToVerify = EmailToVerify::find($id);

        if(!$emailToVerify){
            // no existe una verifycacion de email para este email o ya se verifico o nunca se creo la verificacion para este.
            return response()->json(['error' => 'Invalid email verification link'], Response::HTTP_BAD_REQUEST);
        }
        if ($code !== $emailToVerify->code) {
            return response()->json(['error' => 'Invalid code'], Response::HTTP_BAD_REQUEST);
        }

        $user = $emailToVerify->user;

        if ($user->markEmailAsVerified()) {
            $user->email = $emailToVerify->email;
            $emailToVerify->delete();
            $user->save();
            //event(new Verified($user));
            return response()->json(['message' => 'Email verified'], Response::HTTP_OK);
        }
        return response()->json(['error' => 'Email verification failed'], Response::HTTP_INTERNAL_SERVER_ERROR);

    }
    // genera una peticion de cambio de contraseña , solo 1 cada 3 minutos
    public function forgot_password(Request $request)
    {
        $request->validate([
            'cuil' => [
                'required',
                'string',
                'regex:/^\d{2}-\d{8}-\d{1}$/',
                'exists:users,cuil'
            ]
        ]);

        $user = User::where('cuil', $request->cuil)->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid cuil provided.'], Response::HTTP_BAD_REQUEST);
        }
        $expirationTime = now()->subMinutes(5);
        if ($user->forgotpassword && $user->forgotpassword->created_at > $expirationTime) {
            return response()->json(['message' => 'Wait 5 minutes and try again'], Response::HTTP_BAD_REQUEST);
        }

        $TOKEN = Str::random(20);
        $passwordReset = $user->forgotpassword()->updateOrCreate(
            ['email' => $request->email],
            ['token' => Hash::make($TOKEN)]
        );

        // Send email with the new password
        Mail::to($user->email)->send(new ForgotPassMail($user->name, $TOKEN));

        return response()->json(['message' => 'Password reset successful. Check your email.'], Response::HTTP_OK);
    }
    // el commit de la peticion de cambio de contraseña , asigna la nueva y borra la paticion.
    public function reset_password(Request $request)
    {
        $request->validate([
            'cuil' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string'
        ]);

        $user = User::where('cuil', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], Response::HTTP_NOT_FOUND);
        }

        $passwordReset = $user->forgotpassword();

        if (!$passwordReset) {
            return response()->json(['message' => 'Password reset token not found.'], Response::HTTP_NOT_FOUND);
        }

        if (!Hash::check($request->token, $passwordReset->token)) {
            return response()->json(['message' => 'Invalid token.'], Response::HTTP_UNAUTHORIZED);
        }

        // Update user's password
        $user->password = Hash::make($$request->password);
        $user->save();

        // Delete the password reset token
        $passwordReset->delete();

        return response()->json(['message' => 'Password reset successful. Check your email for the new password.'], Response::HTTP_OK);
    }
    //Change password , si estas logado y conoces tu  ocntraseña anterior
    public function change_password(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|different:current_password|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).+$/',
        ]);
        if (Auth::check()) {
            $user = Auth::user();
            if (!Hash::check($request->input('current_password'), $user->password)) {
                return response()->json(['error' => 'Current password is incorrect'], Response::HTTP_BAD_REQUEST);
            }
            $user->forceFill([
                'password' => Hash::make($request->input('new_password')),
            ]);
            $user->save();
            //Auth::logout();
            $user->tokens()->delete();
            return response()->json(['message' => 'Password changed successfully'], Response::HTTP_OK);
        }

        return response()->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
    }
}
