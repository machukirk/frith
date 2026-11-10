<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when something tried to remove the last owner of the admin panel.
 *
 * This is a backstop, not a validation message. Every screen that could do it
 * hides the control and says why, so reaching this means either a crafted
 * request or two owners acting in the same instant. Both are worth failing
 * loudly for rather than quietly doing nothing.
 */
class LastOwnerStands extends RuntimeException
{
    public static function deleted(): self
    {
        return new self('Refusing to delete the last owner: nobody would be able to sign in to the panel.');
    }

    public static function demoted(): self
    {
        return new self('Refusing to demote the last owner: nobody would be able to sign in to the panel.');
    }
}
