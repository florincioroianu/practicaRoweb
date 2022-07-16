<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

/**
 *
 */
class UserController extends ApiController
{
    
    public function login(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->sendError('Bad request!', $validator->errors()->toArray());
            }

            $error = false;

            /** @var User $user */
            $user = User::where('email', $request->get('email'))->first();
            if (!$user) {
                $error = true;
            } else {
                if (!Hash::check($request->get('password'), $user->password)) {
                    $error = true;
                }
            }

            if ($error) {
                return $this->sendError('Bad credentials!');
            }

            $token = $user->createToken('Practica');

            return $this->sendResponse([
                'token' => $token->plainTextToken,
                'user' => $user->toArray()
            ]);
        } catch (Exception $exception) {
            Log::error($exception);

            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

   
    public function register(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|confirmed'
            ]);

            if ($validator->fails()) {
                return $this->sendError('Bad request!', $validator->errors()->toArray());
            }

            $user = new User();
            $user->name = $request->get('name');
            $user->email = $request->get('email');
            $user->password = Hash::make($request->get('password'));
            $user->email_verified_at = Carbon::now();
            $user->save();

            $token = $user->createToken('Practica');

            return $this->sendResponse([
                'token' => $token->plainTextToken,
                'user' => $user->toArray()
            ]);
        } catch (Exception $exception) {
            Log::error($exception);

            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with(['status' => __($status)])
            : back()->withErrors(['email' => __($status)]);
    }

    public function passwordToken($token)
    {
        return $token;
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(10));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('/login')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }
}
