<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessage;
use App\Support\PageContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

/**
 * The "write to a person" form on the help page.
 *
 * Deliberately plain: it sends an email to the team and stores nothing. There
 * is no ticket, no reference number and no record of the message beyond the
 * inbox it lands in, which is what the page promises.
 *
 * Protected by the honeypot the registration form already uses, not a CAPTCHA.
 * Somebody writing in about a safeguarding worry should not be asked to
 * identify fire hydrants first.
 */
class ContactController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $topics = collect(PageContent::get('help', 'contact.form.topics', []))
            ->pluck('value')
            ->all();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
            'topic' => ['required', 'string', Rule::in($topics)],
            'message' => ['required', 'string', 'min:10', 'max:4000'],
        ], [
            'name.required' => 'We need something to call you when we reply.',
            'email.required' => 'We need an address to reply to.',
            'email.email' => 'That email does not look right. It should look like name@example.com.',
            'topic.required' => 'Which of these is closest?',
            'message.required' => 'Tell us what has happened and we will pick it up.',
            'message.min' => 'A little more detail would help us answer properly.',
        ]);

        Mail::to($this->inboxFor($data['topic']))->send(new ContactMessage($data));

        return redirect()
            ->route('help')
            ->with('contact.sent', true)
            ->withFragment('contact');
    }

    /**
     * Safety goes to the address the page says it goes to, and everything else
     * to the general one. A worried person should not have their message sit
     * behind a week of general enquiries.
     */
    private function inboxFor(string $topic): string
    {
        $routed = PageContent::get('help', 'contact.form.inboxes', []);

        return $routed[$topic] ?? config('frith.company.contact_email');
    }
}
