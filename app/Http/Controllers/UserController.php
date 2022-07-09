<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 *
 */
class UserController extends ApiController
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Bad request!', $validator->messages()->toArray());
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
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|email',
            'password' => 'required'
        ]);


        $name= $request->get('name');
        $email = $request->get('email');
        $password = $request->get('password');

        if ($validator->fails()) {
            return $this->sendError('Bad request!', $validator->messages()->toArray());
        }

        $error = false;
        if (User::where('email', $email)->first()) {
            $error = true;
        }

        if ($error) {
            return $this->sendError('Email already used');
        }

        $user = new User;
        $user->name=$name;
        $user->email = $email;
        $user->password =$password;
        $user->password = Hash::make('password');
        $user->email_verified_at=now();
        $user->remember_token=Str::random(10);
        $user->save();
        return $this->sendResponse($user->toArray());
    }
}
