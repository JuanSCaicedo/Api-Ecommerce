<?php

namespace App\Http\Controllers;

use App\Mail\verifiedMail;
use Illuminate\Http\Request;

use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Mail\ForgotPasswordMail;
use App\Models\User;
use App\Models\UserToken;
use Illuminate\Support\Facades\Mail;
use Validator;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => [
            'login',
            'register',
            'login_ecommerce',
            'verified_auth',
            'verified_email',
            'verified_code',
            'new_password'
        ]]);
    }


    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register()
    {
        $validator = Validator::make(request()->all(), [
            'name' => 'required',
            'surname' => 'required',
            'phone' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $user = new User;
        $user->name = request()->name;
        $user->surname = request()->surname;
        $user->name = request()->name;
        $user->phone = request()->phone;
        $user->type_user = 2;
        $user->email = request()->email;
        $user->uniqd = uniqid();
        $user->password = bcrypt(request()->password);
        $user->save();

        Mail::to(request()->email)->send(new verifiedMail($user));

        return response()->json($user, 201);
    }

    public function verified_email(Request $request)
    {
        $user = User::where("email", $request->email)->first();

        if ($user) {
            $code = uniqid();
            $user->update([
                "code_verified" => $code,
                "code_expires_at" => Carbon::now()->addMinutes(60) // Guarda la fecha y hora actual mas 60 mnts
            ]);
            Mail::to(request()->email)->send(new ForgotPasswordMail($user));
            return response()->json(['message' => 200]);
        } else {
            return response()->json(['message' => 403]);
        }
    }
    public function verified_code(Request $request)
    {
        $user = User::where("code_verified", $request->code)->first();

        if ($user) {
            // Verificar si la fecha de expiración ha pasado
            if ($user->code_expires_at && Carbon::now()->gt($user->code_expires_at)) {
                return response()->json(['message' => 401]);
            }
            return response()->json(['message' => 200]);
        } else {
            return response()->json(['message' => 403]);
        }
    }

    public function new_password(Request $request)
    {
        $user = User::where("code_verified", $request->code)->first();
        $user->update([
            "password" => bcrypt($request->new_password),
            "code_verified" => null,
            "code_expires_at" => null // Asegúrate de anular también la expiración del código
        ]);
        return response()->json(["message" => 200]);
    }
    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $credentials = request(['email', 'password']);

        if (!$token = auth('api')->attempt([
            "email" => request()->email,
            "password" => request()->password,
            "type_user" => 1
        ])) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Obtener User-Agent e IP
        $userAgent = request()->header('User-Agent');
        $ip = request()->ip();

        // Obtener el tiempo de expiración en segundos (TTL)
        $expiresAt = now()->addSeconds(auth('api')->factory()->getTTL() * 60); // Expiración en formato DateTime

        // Almacenar el token en la base de datos con la información adicional
        UserToken::create([
            'id' => Str::uuid()->toString(), // Generamos un id único con `uniqid()`
            'user_id' => auth('api')->user()->id,
            'type_user' => auth('api')->user()->type_user,
            'token' => $token,
            'user_agent' => $userAgent,
            'ip_address' => $ip,
            'expires_at' => $expiresAt,  // Guardamos la fecha de expiración
        ]);

        return $this->respondWithToken($token);
    }

    public function login_ecommerce()
    {
        $credentials = request(['email', 'password']);

        if (!$token = auth('api')->attempt([
            "email" => request()->email,
            "password" => request()->password,
            "type_user" => 2
        ])) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (!auth('api')->user()->email_verified_at) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Obtener User-Agent e IP
        $userAgent = request()->header('User-Agent');
        $ip = request()->ip();

        // Obtener el tiempo de expiración en segundos (TTL)
        $expiresAt = now()->addSeconds(auth('api')->factory()->getTTL() * 60); // Expiración en formato DateTime

        // Almacenar el token en la base de datos con la información adicional
        UserToken::create([
            'id' => Str::uuid()->toString(), // Generamos un id único con `uniqid()`
            'user_id' => auth('api')->user()->id,
            'type_user' => auth('api')->user()->type_user,
            'token' => $token,
            'user_agent' => $userAgent,
            'ip_address' => $ip,
            'expires_at' => $expiresAt,  // Guardamos la fecha de expiración
        ]);

        return $this->respondWithToken($token);
    }

    public function verified_auth(Request $request)
    {
        $user = User::where("uniqd", $request->code_user)->first();

        if ($user) {
            $user->update(["email_verified_at" => now(), "uniqd" => null]);
            return response()->json(["message" => 200]);
        }

        return response()->json(["message" => 403]);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth('api')->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        try {
            // Obtenemos el token actual antes de hacer logout
            $currentToken = auth('api')->getToken();

            // Convertimos el token a string si es necesario
            $tokenString = (string) $currentToken;

            // Buscamos y eliminamos el token de la tabla
            UserToken::where('token', $tokenString)->delete();

            // Hacemos logout
            auth('api')->logout();

            return response()->json(['message' => 'Successfully logged out']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error during logout',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function logout_device(Request $request)
    {
        $user = UserToken::where("token", $request->token)->first();

        if ($user) {
            // Guardamos el token actual de autorización
            $currentToken = auth('api')->getToken();

            try {
                // Configuramos el token que queremos desloguear
                auth('api')->setToken($request->token);

                // Hacemos logout de ese token específico
                auth('api')->logout();

                // Eliminamos el registro de la base de datos
                $user->delete();

                // Restauramos el token original de autorización
                auth('api')->setToken($currentToken);

                return response()->json(['message' => 'Successfully logged token out']);
            } catch (\Exception $e) {
                // En caso de error, nos aseguramos de restaurar el token original
                auth('api')->setToken($currentToken);
                return response()->json(['message' => 'Error logging out token'], 500);
            }
        }

        return response()->json(['message' => 'Token not found'], 404);
    }


    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth('api')->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            "user" => [
                "full_name" => auth('api')->user()->name . ' ' . auth('api')->user()->surname,
                "email" => auth('api')->user()->email,
            ],
        ]);
    }
}
