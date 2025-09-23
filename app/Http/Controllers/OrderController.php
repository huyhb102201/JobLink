<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PayOS\PayOS;

class OrderController extends Controller
{
    public function createOrder(Request $request)
    {
        $body = $request->input();
        $body["amount"] = intval($body["amount"]);
        $body["orderCode"] = intval(substr(strval(microtime(true) * 100000), -6));

        try {
            $response = $this->payOS->createPaymentLink($body);
            return response()->json([
                "error" => 0,
                "message" => "Success",
                "data" => $response["checkoutUrl"]
            ]);
        } catch (\Throwable $th) {
            return $this->handleException($th);
        }
    }

    public function getPaymentLinkInfoOfOrder(string $id)
    {
        try {
            $response = $this->payOS->getPaymentLinkInformation($id);
            return response()->json([
                "error" => 0,
                "message" => "Success",
                "data" => $response["data"]
            ]);
        } catch (\Throwable $th) {
            return $this->handleException($th);
        }
    }

    public function cancelPaymentLinkOfOrder(Request $request, string $id)
    {
        $body = json_decode($request->getContent(), true);
        $cancelBody = is_array($body) && $body["cancellationReason"] ? $body : null;

        try {
            $response = $this->payOS->cancelPaymentLink($id, $cancelBody);
            return response()->json([
                "error" => 0,
                "message" => "Success",
                "data" => $response["data"]
            ]);
        } catch (\Throwable $th) {
            return $this->handleException($th);
        }
    }
}
