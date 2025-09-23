<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use PayOS\PayOS;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected PayOS $payOS;

    public function __construct()
    {
        $this->payOS = new PayOS(
            env("PAYOS_CLIENT_ID"),
            env("PAYOS_API_KEY"),
            env("PAYOS_CHECKSUM_KEY")
        );
    }

    protected function handleException(\Throwable $th)
    {
        return response()->json([
            "error" => $th->getCode(),
            "message" => $th->getMessage(),
            "data" => null
        ]);
    }
}
