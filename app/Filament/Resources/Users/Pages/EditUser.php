<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Support\AdminAccounts;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    public function getHeading(): string
    {
        return $this->record->name;
    }

    public function getSubheading(): ?string
    {
        /** @var User $record */
        $record = $this->record;

        return AdminAccounts::whyLocked(Filament::auth()->user(), $record);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (): bool => AdminAccounts::mayDelete(Filament::auth()->user(), $this->record))
                // Re-checked when pressed, not only when drawn.
                ->before(function (DeleteAction $action): void {
                    if (AdminAccounts::mayDelete(Filament::auth()->user(), $this->record)) {
                        return;
                    }

                    Notification::make()
                        ->danger()
                        ->title('Not removed')
                        ->body(AdminAccounts::whyLocked(Filament::auth()->user(), $this->record))
                        ->send();

                    $action->halt();
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
