<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Route Handler for Account
 * 
 * @author  Azis Hapidin <azishapidin@gmail.com>
 *
 * @link    https://azishapidin.com/
 */
class AccountController extends Controller
{
    /**
     * Show form for create Account.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('account.create');
    }
}
