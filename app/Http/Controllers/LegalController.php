<?php

namespace App\Http\Controllers;

class LegalController extends Controller
{
    public function privacidad()
    {
        return view('legal.privacidad');
    }

    public function cookies()
    {
        return view('legal.cookies');
    }

    public function avisoLegal()
    {
        return view('legal.aviso-legal');
    }

    public function condiciones()
    {
        return view('legal.condiciones');
    }

    public function contratacion()
    {
        return view('legal.contratacion');
    }
}
