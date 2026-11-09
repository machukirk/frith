<?php

namespace App\Support;

use App\Models\Connection;
use App\Models\Registration;

/**
 * What one family is shown about another, worked out once.
 *
 * This is the only place that decides what crosses between two families
 * before they have agreed to talk: a name, a rough area, their children's
 * ages, whether they are a Founder, and a count of what they share. Nothing
 * about what either family is navigating goes through here, which is the
 * promise the home page makes.
 */
class FamilyCard
{
    public function __construct(
        public readonly Registration $family,
        public readonly int $inCommon,
        public readonly float $score,
        public readonly ?Connection $connection,
    ) {}

    public static function make(Registration $viewer, Registration $family): self
    {
        return new self(
            family: $family,
            inCommon: Matching::inCommon($viewer, $family),
            score: Matching::score($viewer, $family),
            connection: Connection::between($viewer, $family),
        );
    }

    /** Their children's ages, which is as much as anybody sees of them. */
    public function childAges(): array
    {
        return $this->family->children->map(fn ($child) => $child->age())->all();
    }

    public function childSummary(): string
    {
        $ages = $this->childAges();

        if ($ages === []) {
            return '';
        }

        $count = count($ages);

        return \Illuminate\Support\Str::of((string) $count)
            ->append(' ', \Illuminate\Support\Str::plural('child', $count))
            ->append(' (aged ', implode(', ', $ages), ')')
            ->value();
    }

    public function commonality(): string
    {
        return $this->inCommon === 1
            ? '1 thing in common'
            : $this->inCommon.' things in common';
    }

    /** What the button on their card should say. */
    public function action(): string
    {
        return match (true) {
            $this->connection?->isAccepted() => 'connected',
            $this->connection?->status === Connection::PENDING
                && $this->connection->requester_id === $this->family->id => 'respond',
            $this->connection?->status === Connection::PENDING => 'waiting',
            $this->connection?->status === Connection::DECLINED => 'closed',
            default => 'ask',
        };
    }
}
