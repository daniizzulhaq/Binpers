<?php

namespace App\Http\Controllers;

use App\Models\PerformanceNote;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NoteImageController extends Controller
{
    public function show(PerformanceNote $note): StreamedResponse
    {
        Gate::authorize('view', $note);

        $disk = Storage::disk('local');

        abort_unless($note->image_path && $disk->exists($note->image_path), 404);

        // Buang output liar (BOM/spasi dari file PHP lain) agar tidak ikut merusak data gambar.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        return $disk->response($note->image_path, null, [
            'Content-Type' => $disk->mimeType($note->image_path) ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
