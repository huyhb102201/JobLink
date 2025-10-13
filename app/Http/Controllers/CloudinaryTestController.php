<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary; // <-- quan trọng

class CloudinaryTestController extends Controller
{
    public function index()
    {
        return view('cloudinary_test');
    }


    public function upload(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:4096',
        ]);

        $result = Cloudinary::uploadApi()->upload(
            $request->file('image')->getRealPath(),
            ['folder' => 'laravel_test']
        );

        // $result là mảng; lấy URL an toàn:
        $url = $result['secure_url'] ?? $result['url'];

        return back()->with('url', $url);
    }
}
