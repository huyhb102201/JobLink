<?php

namespace App\Http\Controllers;
use Cloudinary\Utils;
use Illuminate\Http\Request;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Cloudinary;
use Cloudinary\Transformation\Delivery;

class CloudinaryUploadController extends Controller
{
    public function form()
    {
        return view('cloudinary_upload');
    }


public function store(Request $r)
{
    $r->validate([
        'file' => 'required|file|mimes:zip,rar|max:204800',
    ]);

    $file = $r->file('file');
    $uploadApi = new UploadApi();

    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
    $ext = $file->getClientOriginalExtension();

    $res = $uploadApi->upload(
        $file->getRealPath(),
        [
            'resource_type' => 'raw',
            'public_id' => "uploads/files/{$originalName}",
            'format' => $ext,
            'overwrite' => true
        ]
    );

    // ✅ Tạo link tải về tự động
    $baseUrl = $res['secure_url'] ?? null;

// ✅ Lấy tên gốc của file
$fileName = $originalName . '.' . $ext;

// ✅ Tạo link tải có tên đúng
$downloadUrl = str_replace(
    '/upload/',
    '/upload/fl_attachment:' . $fileName . '/',
    $baseUrl
);

return response()->json([
    'file_name'   => $fileName,
    'url'         => $baseUrl,
    'download'    => $downloadUrl,
    'public_id'   => $res['public_id'] ?? null,
    'bytes'       => $res['bytes'] ?? null,
    'created_at'  => $res['created_at'] ?? null,
]);
}
}
