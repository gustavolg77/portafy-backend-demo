<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CloudinaryService;

class MeController extends Controller
{
    public function __construct(protected CloudinaryService $cloudinary) {}

    // Temporal — borrar después del sprint
    public function testCloudinary(Request $request)
    {
        $request->validate(['foto' => 'required|image|max:2048']);
        try {
            $url = $this->cloudinary->upload($request->file('foto'), 'portafolio/test');
            return response()->json(['status' => 'success', 'url' => $url, 'msg' => '✅ Cloudinary funciona']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'msg' => '❌ ' . $e->getMessage()], 500);
        }
    }
}
