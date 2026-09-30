<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
use App\Support\SiteNavigation;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    public function getHeading(): string
    {
        return $this->record->name;
    }

    public function getBreadcrumb(): string
    {
        return 'Editing';
    }

    protected function getHeaderActions(): array
    {
        return [
            // Editing copy you can't see is guesswork. Opens in a new tab so
            // unsaved changes in the form survive the trip.
            Action::make('view')
                ->label('View the page')
                ->icon('heroicon-o-arrow-top-right-on-square')
                // The page being edited, not always the home page.
                ->url(fn () => SiteNavigation::url($this->record->slug), shouldOpenInNewTab: true)
                ->color('gray'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::id();
        // Compared against what was stored, because whether a null is noise
        // depends entirely on whether it was there before.
        $data['content'] = self::prune($data['content'] ?? [], $this->record->getOriginal('content') ?? []);

        return $data;
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
     * have one — some pages hold a deliberate null, like a contact card with
     * no address of its own. Empty strings are always kept: a field an editor
     * clears comes back as '', and keeping it is what stops the default
     * quietly reappearing.
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
