<?php
    
   
    namespace App\Controllers;
    
    class WipController extends BaseController
    {
        public function index(): string
        {
            return view('wip');
        }
    }
