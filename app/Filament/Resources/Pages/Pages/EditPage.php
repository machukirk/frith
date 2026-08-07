<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
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
                ->url(fn () => route('coming-soon'), shouldOpenInNewTab: true)
                ->color('gray'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::id();

        return $data;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Saved. The page is updated.';
    }
}
