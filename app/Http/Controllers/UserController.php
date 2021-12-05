<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\UserRelation;
use App\Models\User;
use DateTime;
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
            "avatar" => ["required", "file", "mimes:jpg,jpeg,png"]
        ]);

        if ($validator->fails()) {
            return response()->json([
                "messages" => $validator->errors(), 
                "error" => true,
            ], 400);
        }

        $messages = [];

        $filename = md5(time().rand(0,9999)) . ".jpg";
        $destPath = public_path("/media/avatars");
        
        Image::make($data["avatar"]->path())
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

    public function updateCover(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            "cover" => ["required", "file", "mimes:jpg,jpeg,png"]
        ]);

        if ($validator->fails()) {
            return response()->json([
                "messages" => $validator->errors(), 
                "error" => true,
            ], 400);
        }

        $messages = [];

        $filename = md5(time().rand(0,9999)) . ".jpg";
        $destPath = public_path("/media/covers");
        
        Image::make($data["cover"]->path())
            ->fit(850, 310)
            ->save($destPath . "/" . $filename);
        
        $user = User::find($this->loggedUser["id"]);
        $user->cover = $filename;
        $user->save();

        $messages["url"] = url("media/covers/" . $filename);

        return response()->json([
            "error" => false,
            "messages" => $messages,
        ]);
    }

    public function read($id = false)
    {
        $messages = [];

        if ($id) {
            $info = User::find($id);
            if (!$info) {
                return response()->json(["error" => "Usuário inexistente."], 400);
            }

        } else {
            $info = $this->loggedUser;
        }

        $info["avatar"] = url("media/avatars/" . $info["avatar"]);
        $info["cover"] = url("media/covers/" . $info["cover"]);
        
        $info["me"] = $info["id"] == $this->loggedUser["id"] ? true : false;

        $info["age"] = (new DateTime($info["birthdate"]))->diff(new DateTime())->y;

        $info["followers"] = UserRelation::where("user_to", $info["id"])->count();        
        $info["following"] = UserRelation::where("user_from", $info["id"])->count();
        
        $info["photoCount"] = Post::where("id_user", $info["id"])
            ->where("type", "photo")
            ->count();

        $hasRelation = UserRelation::where("user_from", $this->loggedUser["id"])
            ->where("user_to", $info["id"])
            ->count();
        $info["isFollowing"] = $hasRelation > 0 ? true : false;

        $messages["data"] = $info;

        return response()->json([$messages]);
    }
}
