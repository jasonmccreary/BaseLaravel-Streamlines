<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateInvoice;

class InvoicesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->only('store');
    }

    public function store(CreateInvoice $request)
    {
        return redirect()->route('invoice.show', $request->createInvoice());
    }
}
