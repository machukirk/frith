<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
use App\Http\Controllers\PagePreviewController;
use App\Support\SiteNavigation;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    protected string $view = 'filament.pages.edit-page-with-preview';

    public bool $showPreview = true;

    /**
     * Full width, unlike the rest of the panel.
     *
     * Two columns of a page each need to be wide enough to read, and the
     * panel's usual cap leaves the preview too narrow to recognise the page
     * in. With the preview hidden the form goes back to the normal width,
     * because a single column of fields stretched across a monitor is worse,
     * not better.
     */
    public function getMaxContentWidth(): Width|string|null
    {
        return $this->showPreview ? Width::Full : parent::getMaxContentWidth();
    }

    public function getHeading(): string
    {
        return $this->record->name;
    }

    public function getBreadcrumb(): string
    {
        return 'Editing';
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);

        // So the preview shows the current state from the moment it opens,
        // rather than a blank frame until the first field is touched.
        $this->syncPreview();
    }

    /**
     * Keeps the preview in step with the form.
     *
     * Fields are live on blur rather than on every keystroke, so this runs
     * when somebody leaves a field — which is also when they want to see what
     * they have done.
     */
    public function updated(string $property): void
    {
        if (str_starts_with($property, 'data.content')) {
            $this->syncPreview();
            $this->dispatch('preview-changed');
        }
    }

    public function togglePreview(): void
    {
        $this->showPreview = ! $this->showPreview;
    }

    public function previewUrl(): string
    {
        return route('admin.preview', $this->record->slug);
    }

    /**
     * Hands the form's current state to the preview.
     *
     * Through the form's own getState() rather than the raw $data, because a
     * repeater keys its items by UUID in there — a list of FAQ entries comes
     * out as a map, and a page looping over it prints the map rather than the
     * entries. getState() is what turns that back into the shape the page
     * reads, and it is Filament's job to know.
     *
     * It validates on the way, so mid-edit it can throw. The preview then just
     * keeps showing the last good state, which is better than an error page
     * where the page should be.
     */
    private function syncPreview(): void
    {
        $content = rescue(
            fn () => $this->form->getState(afterValidate: null)['content'] ?? null,
            rescue: null,
            report: false,
        );

        session()->put(
            PagePreviewController::key($this->record->slug),
            $content ?? $this->record->content,
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('togglePreview')
                ->label(fn () => $this->showPreview ? 'Hide preview' : 'Show preview')
                ->icon(fn () => $this->showPreview ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                ->color('gray')
                ->action('togglePreview'),

            Action::make('view')
                ->label('Open the live page')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn () => SiteNavigation::url($this->record->slug), shouldOpenInNewTab: true)
                ->color('gray'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::id();
        $data['content'] = self::prune($data['content'] ?? [], $this->record->getOriginal('content') ?? []);

        return $data;
    }

    protected function afterSave(): void
    {
        // The draft has become the page, so the preview should read from the
        // page again rather than from a copy of it.
        session()->forget(PagePreviewController::key($this->record->slug));
    }

    /**
     * Drops what the form added rather than what an editor wrote.
     *
     * The form offers every field that any item in a list uses, so a policy
     * section with paragraphs but no bullets comes back carrying an empty
     * bullet list and a null callout it never had. Storing those would mean
     * opening a page and saving it changed the page, which is the one thing
     * this form must never do.
     *
     * A null or an empty list is dropped only where the stored page did not
     * have one — some pages hold a deliberate null. Empty strings are always
     * kept: a field an editor clears comes back as '', and keeping it is what
     * stops the default quietly reappearing.
     *
     * @param  array<string, mixed>  $content
     * @param  mixed  $original  what was stored at this level
     * @return array<string, mixed>
     */
    private static function prune(array $content, mixed $original): array
    {
        foreach ($content as $key => $value) {
            $stored = is_array($original) && array_key_exists($key, $original) ? $original[$key] : '__absent__';

            if ($value === null) {
                if ($stored === '__absent__') {
                    unset($content[$key]);
                }

                continue;
            }

            if (! is_array($value)) {
                continue;
            }

            $value = self::prune($value, $stored);

            if ($value === [] && $stored === '__absent__') {
                unset($content[$key]);

                continue;
            }

            // Re-index a list so removing an item does not leave a gap that
            // turns it into a map on the next read.
            $content[$key] = array_is_list($content[$key]) ? array_values($value) : $value;
        }

        return $content;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Saved. The page is updated.';
    }
}
