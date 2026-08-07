<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWaitlistSignupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        // The dns check catches gmail.con and similar, which is most of what
        // goes wrong. It's a live lookup, so tests turn it off in phpunit.xml
        // rather than depending on the network.
        $emailFormat = config('frith.waitlist.validate_email_dns')
            ? 'email:rfc,dns'
            : 'email:rfc';

        return [
            // 254 is the longest address the RFC allows.
            'email' => ['required', 'string', $emailFormat, 'max:254'],
        ];
    }

    /**
     * Frith owns the problem, the user is never at fault, and an error shows
     * the shape of the right answer. Brand guidelines §07.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'We need an email address to let you know. Nothing else.',
            'email.email' => 'That email does not look right. It should look like name@example.com.',
            'email.max' => 'That email is too long for us to store. Do check it for a typo.',
        ];
    }
}
