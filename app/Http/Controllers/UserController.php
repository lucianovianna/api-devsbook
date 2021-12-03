<?php

namespace App\Http\Controllers;

use App\Models\User;  
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Image;

class UserController extends Controller
{
    private $loggedUser;
    
    public function __construct()
    {
        $this->middleware("auth:api");

        $this->loggedUser = Auth::user();
    }

    public function update(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'name'             => ['string', 'min:4', 'max:100'],
            'email'            => ['email', 'unique:users,email', 'max:100'],
            'password'         => ['string', 'min:4', 'max:100'],
            'password_confirm' => ['string', 'min:4', 'max:100'],
            'birthdate'        => ['date'],
            'city'             => ['string', 'max:100'],
            'work'             => ['string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                "message" => $validator->errors(), 
                "error" => true,
            ], 400);
        }
        
        $user = User::find($this->loggedUser["id"]);
        $msg = [];

        if (isset($data["name"])) {
            $user->name = $data["name"];
            $msg[] = "Nome atualizado.";
        }
        if (isset($data["email"]) && $data["email"] != $user->email) {
            $user->email = $data["email"];
            $msg[] = "Email atualizado.";
        }
        if (isset($data["birthdate"])) {
            $user->birthdate = $data["birthdate"];
            $msg[] = "Data de nascimento atualizado.";
        }
        if (isset($data["city"])) {
            $user->city = $data["city"];
            $msg[] = "Cidade atualizado.";
        }
        if (isset($data["work"])) {
            $user->work = $data["work"];
            $msg[] = "Trabalho atualizado.";
        }
        if (isset($data["password"])) {
            if (isset($data["password_confirm"]) && $data["password"] === $data["password_confirm"]) {
                $user->password = password_hash($data["password"], PASSWORD_DEFAULT);
                $msg[] = "Senha atualizada.";
            } else {
                return response()->json([
                    "message" => "As senhas não batem.", 
                    "error" => true
                ], 400);
            }
        }

        $user->save();
        $msg[] = "Usuario salvo com sucesso.";

        return response()->json([
            "error" => false,
            "messages" => $msg,
        ]);
    }

    public function updateAvatar(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            "avatar" => ["required", "mimes:jpg,jpeg,png"]
        ]);

        if ($validator->fails()) {
            return response()->json([
                "messages" => $validator->errors(), 
                "error" => true,
            ], 400);
        }

        $messages = [];

        $filename = md5(time().rand(0,9999)) . "jpg";
        $destPath = public_path("/media/avatars");
        
        $img = Image::make($data["avatar"]->path())
            ->fit(200, 200)
            ->save($destPath . "/" . $filename);
        
        $user = User::find($this->loggedUser["id"]);
        $user->avatar = $filename;
        $user->save();

        $messages["url"] = url("media/avatars/" . $filename);

        return response()->json([
            "error" => false,
            "messages" => $messages,
        ]);
    }
}
