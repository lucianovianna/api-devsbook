<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\UserRelation;
use App\Models\PostLike;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\User;
use Image;

class FeedController extends Controller
{
    private $loggedUser;

    public function __construct()
    {
        $this->middleware("auth:api");

        $this->loggedUser = auth()->user();
    }

    /***
     * POST api/feed (type=text/photo, body, photo)
     */
    public function create(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            "type"  => ["required", "in:text,photo"],
            "body"  => ["required_if:type,text", "string"],
            "photo" => ["required_if:type,photo", "file", "mimes:png,jpg,jpeg"],
        ]);

        if ($validator->fails()) {
            return response()->json([
                "messages" => $validator->errors(),
                "error" => true,
            ], 400);
        }

        $messages = [];

        if (isset($data["photo"])) {
            $filename = md5(time() . rand(0, 9999) . ".jpg");
            $destPah = public_path("/media/uploads");

            Image::make($data["photo"]->path())
                ->resize(800, null, function ($constraint) {
                    $constraint->aspectRatio();
                })
                ->save($destPah . "/" . $filename);

            $messages[] = "Imagem processada com sucesso";
            $data["body"] = $filename;
        }
        if (isset($data["body"])) {
            $newPost = new Post();
            $newPost->id_user = $this->loggedUser["id"];
            $newPost->type = $data["type"];
            $newPost->created_at = date("Y-m-d H:i:s");
            $newPost->body = $data["body"];
            $newPost->save();

            $messages[] = "Post enviado com sucesso";
        }

        return response()->json([
            "messages" => $messages,
            "error" => false
        ]);
    }

    public function read(Request $request)
    {
        $messages = [];

        $page = intval($request->input("page"));
        $perPage = 2;

        // 1º: pegar a lista de usuarios que EU sigo (incluindo EU)
        $users = [$this->loggedUser["id"]];
        $userList = UserRelation::where("user_from", $this->loggedUser["id"])->get();

        foreach ($userList as $userItem) {
            $users[] = $userItem["user_to"];
        }

        // 2º: pegar os posts recebidos ordenando pela data
        $postList = Post::whereIn("id_user", $users)
            ->orderBy("created_at", "desc")
            ->offset($page * $perPage)
            ->limit($perPage)
            ->get();

        $total = Post::whereIn("id_user", $users)->count();
        $pageCount = ceil($total / $perPage);

        // 3º: preencher as informações adicionais
        $posts = $this->_postListToObject($postList, $this->loggedUser["id"]);

        $messages["posts"] = $posts;
        $messages["total"] = $total;
        $messages["pageCount"] = $pageCount;
        $messages["currentPage"] = $page;

        return response()->json([$messages]);
    }

    public function userFeed(Request $request, $id = false)
    {
        $messages = [];

        if ($id == false) $id = $this->loggedUser["id"];

        $page = intval($request->input("page"));
        $perPage = 2;

        // Pegar os posts do usuario ordenando pela data
        $postList = Post::where("id_user", $id)
            ->orderBy("created_at", "desc")
            ->offset($page * $perPage)
            ->limit($perPage)
            ->get();

        $total = Post::where("id_user", $id)->count();
        $pageCount = ceil($total / $perPage);

        // Preencher as informações adicionais
        $posts = $this->_postListToObject($postList, $this->loggedUser["id"]);

        $messages["posts"] = $posts;
        $messages["total"] = $total;
        $messages["pageCount"] = $pageCount;
        $messages["currentPage"] = $page;


        return response()->json([$messages]);
    }

    private function _postListToObject($postList, $loggedId)
    {
        foreach ($postList as $postKey => $postItem) {
            // Verificar se o post é meu
            if ($postItem["id_user"] == $loggedId) {
                $postList[$postKey]["mine"] = true;
            } else {
                $postList[$postKey]["mine"] = false;
            }

            // Preencher informações de Usuário
            $userInfo = User::find($postItem["id_user"]);
            $userInfo["avatar"] = url("media/avatars/" . $userInfo["avatar"]);
            $userInfo["cover"] = url("media/covers/" . $userInfo["cover"]);
            $postList[$postKey]["user"] = $userInfo;

            // Preencher informações de Like
            $likes = PostLike::where("id_post", $postItem["id"])->count();
            $postList[$postKey]["likeCount"] = $likes;

            $isLiked = PostLike::where("id_post", $postItem["id"])
                ->where("id_user", $loggedId)
                ->count();
            $postList[$postKey]["liked"] = $isLiked > 0 ? true : false;

            // Preencher informações de Comments
            $comments = PostComment::where("id_post", $postItem["id"])->get();

            foreach ($comments as $commentKey => $comment) {
                $user = User::find($comment["id_user"]);
                $user["avatar"] = url("media/avatars/" . $user["avatar"]);
                $user["cover"] = url("media/covers/" . $user["cover"]);
                $comments[$commentKey]["user"] = $user;
            }
            $postList[$postKey]["comments"] = $comments;
        }

        return $postList;
    }
}
