<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\User;

class SearchController extends Controller
{
    public function __construct()
    {
        $this->middleware("auth:api");
    }

    public function search(Request $request)
    {
        $messages = ["users" => []];

        $data = $request->all();
        $validator = Validator::make($data, [
            "txt" => ["required", "string"]
        ]);

        if ($validator->fails()) {
            return response()->json(["messages" => $validator->errors()], 400);
        }

        // Busca de usuarios
        $userList = User::where("name", "like", "%".$data["txt"]."%")->get();

        foreach ($userList as $userItem) {
            $messages["users"][] = [
                "id" => $userItem["id"],
                "name" => $userItem["name"],
                "avatar" => url("media/avatars/" . $userItem["avatar"])
            ];
        }

        return response()->json([$messages]);
    }
}
