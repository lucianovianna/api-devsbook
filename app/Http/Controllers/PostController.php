<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\PostLike;
use App\Models\Post;

class PostController extends Controller
{
    private $loggedUser;
    
    public function __construct()
    {
        $this->middleware("auth:api");

        $this->loggedUser = auth()->user();
    }

    public function like($id)
    {
        $messages = [];

        if (!Post::find($id)) {
            return response()->json(["error" => "Post inexistente"], 400);
        }

        $isLiked = PostLike::where("id_post", $id)
            ->where("id_user", $this->loggedUser["id"])
            ->count();
        
        if ($isLiked > 0) {
            $postLike = PostLike::where("id_post", $id)
                ->where("id_user", $this->loggedUser["id"])
                ->first();
            
            $postLike->delete();

            $messages["isLiked"] = false;
        } else {
            $newPostLike = new PostLike();
            $newPostLike->id_post = $id;
            $newPostLike->id_user = $this->loggedUser["id"];
            $newPostLike->created_at = date("Y-m-d H:i:s");
            $newPostLike->save();
            
            $messages["isLiked"] = true;
        }

        $messages["likeCout"] = PostLike::where("id_post", $id)->count();

        return response()->json([$messages]);
    }
}
