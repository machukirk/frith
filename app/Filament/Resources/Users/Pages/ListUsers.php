<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Support\AdminAccounts;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    public function getHeading(): string
    {
        return 'Admin accounts';
    }

    public function getSubheading(): ?string
    {
        $owners = AdminAccounts::ownerCount();

        return $owners === 1
            ? 'Who can sign in to this panel. There is one owner, so nobody can remove them until there is a second.'
            : 'Who can sign in to this panel. There is no public sign-up.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Add someone'),
        ];
    }
}
