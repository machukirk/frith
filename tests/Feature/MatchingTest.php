<?php

namespace Tests\Feature;

use App\Models\Registration;
use App\Support\Matching;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MatchingTest extends TestCase
{
    use RefreshDatabase;

    /** @param array<string, mixed> $attributes */
    private function family(string $email, array $attributes = [], array $children = [], array $experiences = []): Registration
    {
        $family = Registration::query()->create(array_merge([
            'email' => $email,
            'first_name' => 'Family',
            'postcode_outcode' => 'TN13',
            'consent_version' => config('frith.consent.version'),
            'consent_text' => config('frith.consent.text'),
            'consented_at' => now(),
        ], $attributes));

        foreach ($children as $i => [$month, $year]) {
            $family->children()->create(['birth_month' => $month, 'birth_year' => $year, 'position' => $i]);
        }

        foreach ($experiences as [$category, $item]) {
            $family->experiences()->create(['category' => $category, 'item' => $item]);
        }

        return $family->load(['children', 'experiences']);
    }

    #[Test]
    public function two_families_with_nothing_in_common_score_zero(): void
    {
        $a = $this->family('a@example.com', ['postcode_outcode' => 'TN13']);
        $b = $this->family('b@example.com', ['postcode_outcode' => 'M1']);

        $this->assertSame(0.0, Matching::score($a, $b));
        $this->assertSame(0, Matching::inCommon($a, $b));
    }

    #[Test]
    public function the_same_family_twice_scores_one(): void
    {
        // A sanity check on the weights: they have to add up to exactly 1, or
        // every score is quietly on the wrong scale.
        $shape = [
            ['support_areas' => ['learning-education'], 'interests' => ['animals'],
                'hopes' => ['local-families'], 'connection_styles' => ['one-to-one']],
            [[3, 2017]],
            [['learning-education', 'school-attendance']],
        ];

        $a = $this->family('a@example.com', ...$shape);
        $b = $this->family('b@example.com', ...$shape);

        $this->assertSame(1.0, Matching::score($a, $b));
    }

    #[Test]
    public function experience_counts_for_more_than_anything_else(): void
    {
        // Frith links by experience rather than by diagnosis, and the
        // weighting is where that belief actually lives.
        $base = ['postcode_outcode' => 'M1'];

        $a = $this->family('a@example.com', $base, [], [['health-wellbeing', 'sleep']]);
        $sharesExperience = $this->family('b@example.com', $base, [], [['health-wellbeing', 'sleep']]);
        $sharesInterests = $this->family('c@example.com', $base + ['interests' => ['animals']], []);

        $a->update(['interests' => ['animals']]);
        $a->refresh()->load(['children', 'experiences']);

        $this->assertGreaterThan(
            Matching::score($a, $sharesInterests),
            Matching::score($a, $sharesExperience),
        );
    }

    #[Test]
    public function a_short_answer_is_not_punished_for_matching_a_long_one(): void
    {
        // Somebody who ticked three statements and shares all three is a
        // perfect experience match, even against a family who ticked ten.
        $few = $this->family('a@example.com', [], [], [
            ['learning-education', 'school-attendance'],
        ]);

        $many = $this->family('b@example.com', [], [], [
            ['learning-education', 'school-attendance'],
            ['learning-education', 'homework-exams'],
            ['health-wellbeing', 'sleep'],
            ['identity-belonging', 'masking'],
        ]);

        // Experience alone is 0.35, and these two share everything the
        // shorter list has.
        $this->assertGreaterThanOrEqual(0.35, Matching::score($few, $many));
    }

    #[Test]
    public function children_within_a_year_count_as_something_in_common(): void
    {
        $a = $this->family('a@example.com', [], [[3, 2017]]);
        $close = $this->family('b@example.com', [], [[9, 2017]]);
        $far = $this->family('c@example.com', [], [[3, 2009]]);

        $this->assertSame(1, Matching::inCommon($a, $close));
        $this->assertSame(0, Matching::inCommon($a, $far));

        // And the nearest pair is what counts, not the first.
        $several = $this->family('d@example.com', [], [[1, 2005], [6, 2017]]);
        $this->assertSame(1, Matching::inCommon($a, $several));
    }

    #[Test]
    public function the_same_postcode_area_is_worth_half_of_the_same_outcode(): void
    {
        $a = $this->family('a@example.com', ['postcode_outcode' => 'TN13']);
        $same = $this->family('b@example.com', ['postcode_outcode' => 'TN13']);
        $nearby = $this->family('c@example.com', ['postcode_outcode' => 'TN9']);
        $away = $this->family('d@example.com', ['postcode_outcode' => 'M1']);

        $this->assertSame(0.2, Matching::score($a, $same));
        $this->assertSame(0.1, Matching::score($a, $nearby));
        $this->assertSame(0.0, Matching::score($a, $away));
    }

    #[Test]
    public function the_count_is_a_tally_of_shared_answers_not_a_score(): void
    {
        // What a family reads is "6 things in common", so it has to be
        // countable against the profile in front of them.
        $a = $this->family('a@example.com', [
            'support_areas' => ['learning-education', 'health-wellbeing'],
            'interests' => ['animals', 'water'],
            'hopes' => ['local-families'],
            'connection_styles' => ['one-to-one'],
        ], [[3, 2017]], [['learning-education', 'school-attendance']]);

        $b = $this->family('b@example.com', [
            'support_areas' => ['learning-education'],
            'interests' => ['animals'],
            'hopes' => ['practical-advice'],
            'connection_styles' => ['one-to-one'],
        ], [[6, 2017]], [['learning-education', 'school-attendance']]);

        // One area, one interest, one connection style, one experience, and
        // children a few months apart. Hopes do not overlap.
        $this->assertSame(5, Matching::inCommon($a, $b));
    }

    #[Test]
    public function a_family_who_answered_nothing_matches_nobody(): void
    {
        // Rather than scoring everybody equally and ordering at random.
        $empty = $this->family('a@example.com', ['postcode_outcode' => 'M1']);
        $full = $this->family('b@example.com', [
            'postcode_outcode' => 'TN13',
            'support_areas' => ['learning-education'],
            'interests' => ['animals'],
        ], [[3, 2017]], [['learning-education', 'school-attendance']]);

        $this->assertSame(0.0, Matching::score($empty, $full));
        $this->assertSame(0, Matching::inCommon($empty, $full));
    }
}
