<?php

namespace App\Controllers;

class AngularController extends BaseController
{
    public function index(): string
    {
        return view('angular_view');
    }
}
