<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SearchController extends Controller
{
    private $loggedUser;
    
    public function __contruct()
    {
        $this->middleware("auth:api");

        $this->loggedUser = auth()->user();
    }
}
