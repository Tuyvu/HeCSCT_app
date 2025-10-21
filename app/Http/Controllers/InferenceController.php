<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InferenceController extends Controller
{
    /**
     * Hiển thị trang suy diễn chính.
     */
    public function show()
    {
        // Tên 'inference' tương ứng với file 'resources/views/inference.blade.php'
        // Chúng ta sẽ tạo file này ở Bước 4.
        return view('inference');
    }

    /**
     * (Tương lai) Xử lý logic khi người dùng bấm "Chạy suy diễn"
     */
    public function run(Request $request)
    {
        // Tạm thời, chỉ trả về dữ liệu đã nhập để kiểm tra
        return response()->json($request->all());
    }
}