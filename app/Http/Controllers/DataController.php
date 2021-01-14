<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Handlers\ItemHandler as ItemHandler;

class DataController extends Controller
{
    public function getClasses(){
        $classes = ItemHandler::getClasses();
        return view('scaffolding', ['classes' => $classes]);
    }
}
