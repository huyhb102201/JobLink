<!doctype html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Test Upload Ảnh - Cloudinary</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-5">
  <div class="container">
    <h2 class="mb-4 text-primary">Test Upload Ảnh lên Cloudinary 🚀</h2>

    <form action="{{ route('cloudinary.upload') }}" method="POST" enctype="multipart/form-data">
      @csrf
      <div class="mb-3">
        <label for="image" class="form-label">Chọn ảnh:</label>
        <input type="file" name="image" id="image" class="form-control" required>
      </div>
      <button class="btn btn-success">Upload</button>
    </form>

    @if (session('url'))
      <div class="mt-4">
        <h5>Ảnh đã upload thành công:</h5>
        <img src="{{ session('url') }}" alt="Uploaded" class="img-thumbnail" style="max-width:300px;">
        <p class="mt-2">
          <strong>URL:</strong>
          <a href="{{ session('url') }}" target="_blank">{{ session('url') }}</a>
        </p>
      </div>
    @endif
  </div>
</body>
</html>
