<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function home()
    {
        return redirect()->route('login');
    }

    public function login()
    {
        return view('pages.login');
    }

    public function dashboard()
    {
        return view('pages.dashboard');
    }

    public function debtsIndex()
    {
        return view('pages.debts.index');
    }

    public function debtsCreate()
    {
        return view('debts.create');
    }

    public function debtsShow(string $id)
    {
        return view('pages.debts.show', [
            'debtRecordId' => $id,
        ]);
    }

    public function notifications()
    {
        return view('pages.notifications');
    }

    public function students()
    {
        return view('pages.students');
    }

    public function studentsCreate()
    {
        return view('pages.students.create');
    }

    public function profile()
    {
        return view('pages.profile');
    }
}
