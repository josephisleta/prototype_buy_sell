<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;

use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

use App\Http\Requests;

class OAuthController extends Controller
{
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function authenticate()
    {
        $credentials = $this->request->only(['email', 'password']);

        $validator = Validator::make($credentials, [
            'email' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            $data = [
                'success' => false,
                'error' => $validator->errors()->all()
            ];
            return response()->json($data);
        }
        
        try {
            $custom_claims = ($this->request->remember == 1) ? ['exp' => strtotime('+14 day', time())] : [];
            
            if (! $token = JWTAuth::attempt($credentials, $custom_claims)) {
                $data = [
                    'success' => false,
                    'error' => ['Invalid email or password.']
                ];
                return response()->json($data);
            }
        } catch (JWTException $e) {
            $data = [
                'success' => false,
                'error' => ['Could not create token.']
            ];
            return response()->json($data);
        }

        return response()->json([
            'token' => $token
        ]);
    }

    public function register()
    {
        $error = [];

        if (!$this->request->username) $error[] = 'Username is required.';
        if (!$this->request->firstname) $error[] = 'First name is required.';
        if (!$this->request->lastname) $error[] = 'Last name is required.';
        if (!$this->request->email) $error[] = 'Email is required.';
        if (!$this->request->password) $error[] = 'Password is required.';

        if (User::where('username', $this->request->username)->first()) $error[] = 'Username already exists.';
        if (User::where('email', $this->request->email)->first()) $error[] = 'Email already exists.';

        if ($error) return response()->json([
            'success' => false,
            'error' => $error
        ]);

        $request = [
            'username' => $this->request->username,
            'firstname' => $this->request->firstname,
            'lastname' => $this->request->lastname,
            'email' => $this->request->email,
            'password' => Hash::make($this->request->password)
        ];

        User::create($request);

        return response()->json([
            'success' => true,
            'error' => $error
        ]);
    }
}
