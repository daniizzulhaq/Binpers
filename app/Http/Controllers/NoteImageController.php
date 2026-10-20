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

        return $disk->response($note->image_path, null, [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
