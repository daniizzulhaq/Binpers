@if ($note->image_path)
    <a href="{{ route('notes.image', $note) }}" target="_blank" rel="noopener" class="d-block mt-2">
        <img src="{{ route('notes.image', $note) }}" alt="Foto catatan" loading="lazy"
             class="img-fluid rounded border"
             style="max-height: {{ $height ?? 220 }}px; object-fit: cover;">
    </a>
@endif
