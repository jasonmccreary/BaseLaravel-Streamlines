<?php

namespace App\Exceptions;

use Exception;

class PaymentChargeException extends Exception
{
    private $data;

    public function __construct($message, $code, $data)
    {
        $this->data = $data;

        parent::__construct($message, $code);
    }

    /**
     * Report the exception.
     *
     * @return void
     */
    public function report()
    {
        Log::error('Card failed: ', $this->data);
    }

    /**
     * Render the exception as an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function render($request)
    {
        return redirect()->back()
            ->withInput($request->input())
            ->with('error', [
                'template'=> 'partials.errors.charge_failed',
                'data' => $this->data['error'],
            ]);
    }
}
