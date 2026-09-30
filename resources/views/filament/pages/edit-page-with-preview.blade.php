{{--
    The edit screen, with the page itself beside it.

    The preview is an iframe of the real page rendered from the form's unsaved
    state — same Blade, same stylesheet — so what an editor looks at is the
    page rather than a drawing of it. It reloads when they leave a field,
    which is also when they want to see what they did.
--}}
<x-filament-panels::page>

    <div @class(['page-editor', 'page-editor--split' => $this->showPreview])>

        <div class="page-editor__form">
            {{ $this->content }}
        </div>

        @if ($this->showPreview)
            <aside class="page-editor__preview"
                   x-data="{
                       reload() {
                           const frame = $refs.frame;
                           if (! frame) return;
                           // Keep the reader where they were rather than
                           // throwing them to the top on every edit.
                           const y = frame.contentWindow?.scrollY ?? 0;
                           frame.addEventListener('load', () => {
                               frame.contentWindow?.scrollTo(0, y);
                           }, { once: true });
                           frame.contentWindow?.location.reload();
                       },
                   }"
                   x-on:preview-changed.window="reload()">

                <div class="page-editor__bar">
                    <span class="page-editor__label">Live preview</span>
                    <span class="page-editor__hint">Updates when you leave a field</span>
                    <button type="button" class="page-editor__refresh" x-on:click="reload()">Refresh</button>
                </div>

                <div class="page-editor__frame-wrap">
                    <iframe class="page-editor__frame"
                            x-ref="frame"
                            src="{{ $this->previewUrl() }}"
                            title="Preview of {{ $this->record->name }}"></iframe>
                </div>
            </aside>
        @endif
    </div>

</x-filament-panels::page>
