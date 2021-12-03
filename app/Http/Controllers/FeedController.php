<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Post;
Use Image;

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
            $filename = md5(time().rand(0,9999) . ".jpg");
            $destPah = public_path("/media/uploads");

            Image::make($data["photo"]->path())
                ->resize(800, null, function($constraint) {
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
}
