<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\User;
use PDOException;

class AuthController extends Controller
{
    public function __contruct()
    {
        $this->middleware("auth:api", [
            "except" => [
                "login",
                "create, 
                unauthorized"
            ]
        ]);
    }

    public function create(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'min:4', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email', 'max:100'],
            'password' => ['required', 'string', 'min:4', 'max:100'],
            'birthdate' => ['required', 'date']
        ]);

        if ($validator->fails()) {
            $response['message'] = $validator->errors();
            $response['error'] = true;
            return response()->json($response, 400);
        }

        try {
            $user = new User();
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->password = password_hash($data['password'], PASSWORD_DEFAULT);
            $user->birthdate = $data['birthdate'];
            $user->save();

            $token = Auth::attempt([
                'email' => $data['email'],
                'password' => $data['password']
            ]);

            if ($token) {
                return response()->json(['error' => false, 'token' => $token], 200);
            }
        } catch (PDOException $exception) {
            return response()->json(['error' => true, 'message' => $exception->getMessage()], 400);
        }
    }
}
