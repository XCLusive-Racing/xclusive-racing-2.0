<?php

namespace Tests\Unit;

use App\Enums\SafetyRatingGrade;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SafetyRatingGradeTest extends TestCase
{
    public static function boundaries(): array
    {
        return [
            '2.99 is D' => [2.99, 'D', '#ffffff'],
            '3.00 is C' => [3.00, 'C', '#ff8000'],
            '4.99 is C' => [4.99, 'C', '#ff8000'],
            '5.00 is B' => [5.00, 'B', '#cc0000'],
            '5.99 is B' => [5.99, 'B', '#cc0000'],
            '6.00 is A' => [6.00, 'A', '#47b417'],
            '6.99 is A' => [6.99, 'A', '#47b417'],
            '7.00 is X' => [7.00, 'X', '#3c81f3'],
            '7.99 is X' => [7.99, 'X', '#3c81f3'],
            '8.00 is Y' => [8.00, 'Y', '#ffc71d'],
            '8.99 is Y' => [8.99, 'Y', '#ffc71d'],
            '9.00 is Z' => [9.00, 'Z', '#a928ff'],
            '9.99 is Z' => [9.99, 'Z', '#a928ff'],
        ];
    }

    #[DataProvider('boundaries')]
    public function test_every_boundary_maps_to_the_right_letter_and_colour(float $rating, string $letter, string $colour): void
    {
        $grade = SafetyRatingGrade::fromRating($rating);

        $this->assertSame($letter, $grade->value);
        $this->assertSame($colour, $grade->color());
    }

    public function test_an_sr_5_requirement_is_a_red_b_and_sr_7_is_x(): void
    {
        $this->assertSame('B', SafetyRatingGrade::fromRating(5.00)->value);
        $this->assertSame('#cc0000', SafetyRatingGrade::fromRating(5.00)->color());
        $this->assertSame('X', SafetyRatingGrade::fromRating(7.00)->value);
    }

    public function test_out_of_range_values_do_not_throw(): void
    {
        $this->assertSame(SafetyRatingGrade::D, SafetyRatingGrade::fromRating(-1.0));
        $this->assertSame(SafetyRatingGrade::D, SafetyRatingGrade::fromRating(0.0));
        $this->assertSame(SafetyRatingGrade::Z, SafetyRatingGrade::fromRating(10.0));
        $this->assertSame(SafetyRatingGrade::Z, SafetyRatingGrade::fromRating(1000.0));
    }

    public function test_the_scale_runs_d_to_z_with_the_expected_ranges(): void
    {
        $ranges = [];
        foreach (SafetyRatingGrade::scale() as $grade) {
            $ranges[$grade->value] = $grade->rangeLabel();
        }

        $this->assertSame([
            'D' => '0.00 - 2.99',
            'C' => '3.00 - 4.99',
            'B' => '5.00 - 5.99',
            'A' => '6.00 - 6.99',
            'X' => '7.00 - 7.99',
            'Y' => '8.00 - 8.99',
            'Z' => '9.00 - 9.99',
        ], $ranges);
    }

    public function test_short_labels_sit_next_to_the_thresholds(): void
    {
        $short = [];
        foreach (SafetyRatingGrade::scale() as $grade) {
            $short[$grade->value] = $grade->shortLabel();
        }

        $this->assertSame(['D' => '<3', 'C' => '3+', 'B' => '5+', 'A' => '6+', 'X' => '7+', 'Y' => '8+', 'Z' => '9+'], $short);
    }

    public function test_each_short_label_matches_its_lower_bound(): void
    {
        foreach (SafetyRatingGrade::scale() as $grade) {
            if ($grade === SafetyRatingGrade::D) {
                continue;
            }
            $this->assertSame((string) (int) $grade->min().'+', $grade->shortLabel());
        }
    }

    public function test_aria_label_states_the_grade_and_full_range(): void
    {
        $this->assertSame('Safety rating B, 5.00 to 5.99', SafetyRatingGrade::B->ariaLabel());
        $this->assertSame('Safety rating D, 0.00 to 2.99', SafetyRatingGrade::D->ariaLabel());
        $this->assertSame('Safety rating Z, 9.00 to 9.99', SafetyRatingGrade::Z->ariaLabel());
    }

    public function test_d_uses_a_dark_text_colour_because_its_base_colour_is_white(): void
    {
        $this->assertSame('#374151', SafetyRatingGrade::D->textColor());
        $this->assertSame('#cc0000', SafetyRatingGrade::B->textColor());
    }

    public function test_requirement_map_matches_the_enum_for_whole_numbers(): void
    {
        $map = SafetyRatingGrade::requirementMap();

        $this->assertSame(['B', '#cc0000'], $map['5']);
        $this->assertSame(['A', '#47b417'], $map['6']);
        $this->assertSame(['X', '#3c81f3'], $map['7']);
        $this->assertSame(['Z', '#a928ff'], $map['9']);
    }
}
