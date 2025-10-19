<?php

namespace App\Http\Controllers;

class PageController extends Controller
{
    public function dashboard()
    {
        return view('pages.dashboard');
    }
    public function item()
    {
        return view('pages.item');
    }
}
