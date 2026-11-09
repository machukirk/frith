<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use App\Support\FamilyCard;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * One family, as another family sees them.
 *
 * Reached by public_id rather than by a slug of their name: two families can
 * both be "Fun House", a name can change, and a link should not break or
 * leak when it does.
 */
class FamilyController extends Controller
{
    public function show(Request $request, string $publicId): View
    {
        $viewer = $request->user('founder');

        $family = Registration::query()
            ->with(['children', 'experiences'])
            ->where('public_id', $publicId)
            ->firstOrFail();

        // A blocked family is not findable, and the block is silent: this is
        // the same 404 somebody would get for a name that never existed. A
        // family who has asked to leave gets the same treatment, from the
        // moment they press the button rather than when the row goes.
        abort_if(
            $family->is($viewer) || $family->hasLeft() || $viewer->isSeparatedFrom($family),
            404,
        );

        return view('pages.family.show', [
            'card' => FamilyCard::make($viewer->loadMissing(['children', 'experiences']), $family),
        ]);
    }
}
