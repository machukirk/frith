<?php

namespace App\Filament\Resources\Forms\Pages;

use App\Filament\Resources\Forms\FormResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditForm extends EditRecord
{
    protected static string $resource = FormResource::class;

    public function getHeading(): string
    {
        return $this->record->name;
    }

    public function getBreadcrumb(): string
    {
        return 'Wording';
    }

    public static function getNavigationLabel(): string
    {
        return 'Wording';
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-pencil-square';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label('Try the form')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn () => route('register.start'), shouldOpenInNewTab: true)
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
        return 'Saved. The form is updated.';
    }
}
