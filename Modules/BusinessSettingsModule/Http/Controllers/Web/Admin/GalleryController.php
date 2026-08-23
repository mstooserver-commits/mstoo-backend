<?php

namespace Modules\BusinessSettingsModule\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BusinessSettingsModule\Services\GalleryService;

class GalleryController extends Controller
{
    public function __construct(private GalleryService $gallery)
    {
    }

    public function index(Request $request): View
    {
        $media = $this->gallery->paginate($request->get('search'), $request->get('type'));
        $can_edit = access_checker('system_management', 'edit');

        return view('businesssettingsmodule::admin.system-setup.gallery', compact('media', 'can_edit'));
    }

    public function upload(Request $request): RedirectResponse
    {
        $request->validate([
            'images' => 'required|array|min:1|max:12',
            'images.*' => 'required|file|max:2048',
        ]);

        $uploaded = 0;
        $errors = [];
        foreach ((array) $request->file('images', []) as $file) {
            try {
                $this->gallery->store($file);
                $uploaded++;
            } catch (\Throwable $exception) {
                $errors[] = $file->getClientOriginalName() . ': ' . $exception->getMessage();
            }
        }

        if ($uploaded > 0) {
            admin_audit('system.gallery.uploaded', 'gallery', ['count' => $uploaded]);
            Toastr::success(translate('Media uploaded successfully'));
        }
        foreach ($errors as $error) {
            Toastr::error($error);
        }

        return back();
    }

    public function show(string $filename): JsonResponse
    {
        $filename = basename($filename);
        $item = $this->gallery->describe($filename);
        if (empty($item)) {
            return response()->json(['message' => translate('File not found')], 404);
        }

        return response()->json($item);
    }

    public function destroy(string $filename): RedirectResponse
    {
        $result = $this->gallery->delete(basename($filename));
        if (!empty($result['ok'])) {
            admin_audit('system.gallery.deleted', $filename);
            Toastr::success(translate('Media deleted successfully'));
            return back();
        }

        Toastr::error($result['message'] ?? translate('Unable to delete this file'));
        return back();
    }
}
