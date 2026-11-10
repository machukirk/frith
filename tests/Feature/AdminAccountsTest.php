<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Exceptions\LastOwnerStands;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Support\AdminAccounts;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The Users area, and the two things it must never allow: an editor promoting
 * themselves, and the last owner going away.
 */
class AdminAccountsTest extends TestCase
{
    use RefreshDatabase;

    private function owner(array $overrides = []): User
    {
        return User::factory()->create(['role' => UserRole::Owner, ...$overrides]);
    }

    private function editor(array $overrides = []): User
    {
        return User::factory()->create(['role' => UserRole::Editor, ...$overrides]);
    }

    // --- Who may see it at all ----------------------------------------------

    #[Test]
    public function only_an_owner_can_see_the_users_area(): void
    {
        $this->actingAs($this->owner());
        $this->assertTrue(UserResource::canViewAny());

        $this->actingAs($this->editor());
        $this->assertFalse(UserResource::canViewAny());
    }

    #[Test]
    public function an_editor_is_refused_the_list_the_create_and_the_edit_screen(): void
    {
        // An editor who could reach this screen could make themselves an
        // owner, and an owner sees every family's email address. That is the
        // one piece of access the editor role exists to withhold.
        $editor = $this->editor();
        $owner = $this->owner();

        $this->actingAs($editor);

        $this->get(UserResource::getUrl('index'))->assertForbidden();
        $this->get(UserResource::getUrl('create'))->assertForbidden();
        $this->get(UserResource::getUrl('edit', ['record' => $owner]))->assertForbidden();
    }

    #[Test]
    public function an_editor_cannot_promote_anybody_by_posting_the_form(): void
    {
        $editor = $this->editor();
        $this->owner();

        $this->actingAs($editor);

        Livewire::test(EditUser::class, ['record' => $editor->getKey()])
            ->assertForbidden();

        $this->assertSame(UserRole::Editor, $editor->fresh()->role);
    }

    #[Test]
    public function an_owner_sees_the_area_and_can_open_it(): void
    {
        $owner = $this->owner();
        $this->editor();

        $this->actingAs($owner);

        $this->get(UserResource::getUrl('index'))->assertOk();

        Livewire::test(ListUsers::class)
            ->assertCanSeeTableRecords(User::query()->get());
    }

    // --- Adding somebody -----------------------------------------------------

    #[Test]
    public function an_owner_can_add_an_editor(): void
    {
        $this->actingAs($this->owner());

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Lucy Mackle',
                'email' => 'lucy@example.com',
                'role' => UserRole::Editor->value,
                'password' => 'a-long-enough-passphrase-9471',
                'password_confirmation' => 'a-long-enough-passphrase-9471',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $added = User::query()->where('email', 'lucy@example.com')->sole();

        $this->assertSame(UserRole::Editor, $added->role);
        $this->assertTrue(Hash::check('a-long-enough-passphrase-9471', $added->password));
    }

    #[Test]
    public function a_new_account_needs_a_password_of_at_least_twelve_characters(): void
    {
        $this->actingAs($this->owner());

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Lucy Mackle',
                'email' => 'lucy@example.com',
                'role' => UserRole::Editor->value,
                'password' => 'short',
                'password_confirmation' => 'short',
            ])
            ->call('create')
            ->assertHasFormErrors(['password']);

        $this->assertDatabaseMissing('users', ['email' => 'lucy@example.com']);
    }

    #[Test]
    public function two_accounts_cannot_share_an_address(): void
    {
        $existing = $this->editor(['email' => 'taken@example.com']);
        $this->actingAs($this->owner());

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Somebody Else',
                'email' => 'taken@example.com',
                'role' => UserRole::Editor->value,
                'password' => 'a-long-enough-passphrase-9471',
                'password_confirmation' => 'a-long-enough-passphrase-9471',
            ])
            ->call('create')
            ->assertHasFormErrors(['email']);

        $this->assertSame(1, User::query()->where('email', 'taken@example.com')->count());
    }

    // --- Editing -------------------------------------------------------------

    #[Test]
    public function leaving_the_password_blank_keeps_the_one_they_have(): void
    {
        // Otherwise every correction of somebody's name resets their password
        // and locks them out until an owner notices.
        $owner = $this->owner();
        $editor = $this->editor(['password' => Hash::make('their-own-passphrase-55')]);

        $this->actingAs($owner);

        Livewire::test(EditUser::class, ['record' => $editor->getKey()])
            ->fillForm(['name' => 'Corrected Name', 'password' => ''])
            ->call('save')
            ->assertHasNoFormErrors();

        $editor->refresh();

        $this->assertSame('Corrected Name', $editor->name);
        $this->assertTrue(Hash::check('their-own-passphrase-55', $editor->password));
    }

    #[Test]
    public function an_owner_can_change_somebody_elses_password(): void
    {
        $owner = $this->owner();
        $editor = $this->editor();

        $this->actingAs($owner);

        Livewire::test(EditUser::class, ['record' => $editor->getKey()])
            ->fillForm([
                'password' => 'a-brand-new-passphrase-3388',
                'password_confirmation' => 'a-brand-new-passphrase-3388',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue(Hash::check('a-brand-new-passphrase-3388', $editor->fresh()->password));
    }

    #[Test]
    public function a_password_typed_twice_differently_is_refused(): void
    {
        // There is no reset email for admin accounts, so a password typed once
        // and never seen again is how somebody locks themselves out with a
        // typo they cannot look up.
        $this->actingAs($this->owner());

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Lucy Mackle',
                'email' => 'lucy@example.com',
                'role' => UserRole::Editor->value,
                'password' => 'a-long-enough-passphrase-9471',
                'password_confirmation' => 'a-long-enough-passphrase-0000',
            ])
            ->call('create')
            ->assertHasFormErrors(['password']);

        $this->assertDatabaseMissing('users', ['email' => 'lucy@example.com']);
    }

    // --- Not locking everybody out -------------------------------------------

    #[Test]
    public function the_only_owner_cannot_be_deleted_by_anybody(): void
    {
        $only = $this->owner();
        $editor = $this->editor();

        $this->assertTrue(AdminAccounts::isLastOwner($only));
        $this->assertFalse(AdminAccounts::mayDelete($only, $only));
        $this->assertFalse(AdminAccounts::mayDelete($editor, $only));
        $this->assertFalse(UserResource::canDelete($only));

        // And not the role either, or the panel is just as unreachable.
        $this->assertFalse(AdminAccounts::mayChangeRole($only, $only));
    }

    #[Test]
    public function the_only_owner_cannot_demote_themselves_through_the_form(): void
    {
        $only = $this->owner();

        $this->actingAs($only);

        Livewire::test(EditUser::class, ['record' => $only->getKey()])
            ->fillForm(['role' => UserRole::Editor->value])
            ->call('save');

        // The field is disabled and undehydrated, so the posted value is
        // discarded rather than refused. Either way the role has to survive.
        $this->assertSame(UserRole::Owner, $only->fresh()->role);
        $this->assertSame(1, AdminAccounts::ownerCount());
    }

    #[Test]
    public function a_second_owner_makes_the_first_one_removable(): void
    {
        // The normal way to hand the panel over: promote, then demote.
        $first = $this->owner();
        $second = $this->owner();

        $this->assertFalse(AdminAccounts::isLastOwner($first));
        $this->assertTrue(AdminAccounts::mayDelete($second, $first));
        $this->assertTrue(AdminAccounts::mayChangeRole($second, $first));
    }

    #[Test]
    public function nobody_can_delete_their_own_account(): void
    {
        // Deleting the account you are signed in as logs you out mid-action
        // and reads as a bug.
        $owner = $this->owner();
        $this->owner();

        $this->assertFalse(AdminAccounts::mayDelete($owner, $owner));

        $this->actingAs($owner);

        Livewire::test(EditUser::class, ['record' => $owner->getKey()])
            ->assertActionHidden(TestAction::make('delete'));

        $this->assertDatabaseHas('users', ['id' => $owner->id]);
    }

    #[Test]
    public function an_owner_can_delete_another_account(): void
    {
        $owner = $this->owner();
        $this->owner();
        $editor = $this->editor();

        $this->actingAs($owner);

        Livewire::test(EditUser::class, ['record' => $editor->getKey()])
            ->callAction(TestAction::make('delete'));

        $this->assertDatabaseMissing('users', ['id' => $editor->id]);
    }

    #[Test]
    public function there_is_no_way_to_delete_several_at_once(): void
    {
        // The two things this screen must never allow are removing the last
        // owner and removing yourself, and a checkbox column is the easiest
        // way to do both by accident.
        $this->assertFalse(UserResource::canDeleteAny());

        $this->actingAs($this->owner());

        // Asserted against the table's own configuration rather than by name:
        // the per-row action is also called "delete", so looking it up by name
        // finds that one and passes for the wrong reason.
        $table = Livewire::test(ListUsers::class)->instance()->getTable();

        $this->assertSame([], $table->getToolbarActions());
        $this->assertFalse($table->isSelectionEnabled());
    }

    #[Test]
    public function the_screen_says_why_a_control_is_missing(): void
    {
        $only = $this->owner();

        $this->assertStringContainsString(
            'the only owner',
            AdminAccounts::whyLocked($only, $only),
        );

        $second = $this->owner();

        $this->assertStringContainsString(
            'This is you',
            AdminAccounts::whyLocked($second, $second),
        );

        $this->assertNull(AdminAccounts::whyLocked($second, $only));
    }

    #[Test]
    public function the_rules_refuse_a_signed_out_actor(): void
    {
        $owner = $this->owner();

        $this->assertFalse(AdminAccounts::mayManage(null));
        $this->assertFalse(AdminAccounts::mayDelete(null, $owner));
        $this->assertFalse(AdminAccounts::mayChangeRole(null, $owner));
    }

    // --- The backstop, under the screen ---------------------------------------
    //
    // The screen hides the controls that would empty the panel and says why.
    // That is the right place to explain it and the wrong place to enforce it:
    // a screen only governs the paths it knows about. These attack the model.

    #[Test]
    public function the_last_owner_cannot_be_deleted_however_it_is_attempted(): void
    {
        $only = $this->owner();
        $this->editor();

        // Straight at the model, past every Filament gate, the way tinker, a
        // console command or a crafted request would arrive.
        $this->expectException(LastOwnerStands::class);

        $only->delete();
    }

    #[Test]
    public function the_last_owner_survives_the_attempt(): void
    {
        $only = $this->owner();

        try {
            $only->delete();
        } catch (LastOwnerStands) {
            // Expected.
        }

        $this->assertDatabaseHas('users', ['id' => $only->id]);
        $this->assertSame(1, AdminAccounts::ownerCount());
    }

    #[Test]
    public function the_last_owner_cannot_be_demoted_however_it_is_attempted(): void
    {
        $only = $this->owner();

        $this->expectException(LastOwnerStands::class);

        $only->update(['role' => UserRole::Editor]);
    }

    #[Test]
    public function a_second_owner_makes_both_operations_allowed_again(): void
    {
        $first = $this->owner();
        $second = $this->owner();

        // Not the last one any more, so both paths open.
        $first->update(['role' => UserRole::Editor]);
        $this->assertSame(UserRole::Editor, $first->fresh()->role);

        $this->assertSame(1, AdminAccounts::ownerCount());

        // And now the remaining owner is protected again.
        $this->expectException(LastOwnerStands::class);
        $second->delete();
    }

    #[Test]
    public function an_editor_can_always_be_removed(): void
    {
        $this->owner();
        $editor = $this->editor();

        $editor->delete();

        $this->assertDatabaseMissing('users', ['id' => $editor->id]);
    }

    #[Test]
    public function promoting_somebody_is_never_blocked(): void
    {
        // The guard is about the last owner going away, not about owners
        // appearing. Promotion is how you get a second one.
        $this->owner();
        $editor = $this->editor();

        $editor->update(['role' => UserRole::Owner]);

        $this->assertSame(UserRole::Owner, $editor->fresh()->role);
        $this->assertSame(2, AdminAccounts::ownerCount());
    }

    #[Test]
    public function the_guard_reads_the_count_under_a_lock(): void
    {
        // The reason this is on the model and not only on the screen: two
        // owners acting in the same instant each read a count of 2 from their
        // own snapshot, both pass an unlocked check, and the panel ends up
        // with none. lockForUpdate makes the second wait and see the truth.
        //
        // The suite runs on sqlite, whose grammar drops the lock silently, so
        // a compiled-SQL assertion here would pass for the wrong reason and a
        // race cannot be reproduced at all. This pins the intent instead: the
        // locking read exists, it agrees with the plain one, and the guards
        // use it. Checked against a real MariaDB in the deploy preflight.
        $this->owner();
        $this->owner();
        $this->editor();

        $this->assertSame(2, AdminAccounts::ownerCount());
        $this->assertSame(2, AdminAccounts::ownerCountForUpdate());

        $source = file_get_contents(app_path('Support/AdminAccounts.php'));

        $this->assertStringContainsString('lockForUpdate()', $source);

        // And the model's guards must be the ones using it. An unlocked read
        // here is the whole bug.
        $model = file_get_contents(app_path('Models/User.php'));

        $this->assertSame(2, substr_count($model, 'ownerCountForUpdate()'));
        $this->assertStringNotContainsString('AdminAccounts::ownerCount()', $model);
    }
}
