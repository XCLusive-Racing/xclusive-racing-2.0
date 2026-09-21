<?php

namespace App\Enums;

/**
 * The one and only mapping from a Safety Rating value to its grade letter and colour.
 *
 * Lower bound inclusive, upper bound exclusive (Z runs up to and including 9.99):
 *   D below 3.00, C 3.00-4.99, B 5.00-5.99, A 6.00-6.99, X 7.00-7.99, Y 8.00-8.99, Z 9.00-9.99.
 *
 * Everything that shows an SR letter or colour (event requirements, driver
 * profiles, lists, admin previews, the SR scale component) reads it from here.
 */
enum SafetyRatingGrade: string
{
    case D = 'D';
    case C = 'C';
    case B = 'B';
    case A = 'A';
    case X = 'X';
    case Y = 'Y';
    case Z = 'Z';

    /** Lowest rating that belongs to this grade (inclusive). */
    public function min(): float
    {
        return match ($this) {
            self::D => 0.00,
            self::C => 3.00,
            self::B => 5.00,
            self::A => 6.00,
            self::X => 7.00,
            self::Y => 8.00,
            self::Z => 9.00,
        };
    }

    /** Upper limit (exclusive). Z's real ceiling is the 9.99 cap, shown as 10.00 here. */
    public function max(): float
    {
        return match ($this) {
            self::D => 3.00,
            self::C => 5.00,
            self::B => 6.00,
            self::A => 7.00,
            self::X => 8.00,
            self::Y => 9.00,
            self::Z => 10.00,
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::D => '#ffffff',
            self::C => '#ff8000',
            self::B => '#cc0000',
            self::A => '#47b417',
            self::X => '#3c81f3',
            self::Y => '#ffc71d',
            self::Z => '#a928ff',
        };
    }

    /** Human range for display, e.g. "5.00 - 5.99". */
    public function rangeLabel(): string
    {
        return number_format($this->min(), 2).' - '.number_format($this->max() - 0.01, 2);
    }

    /** Short threshold label for tight spaces (phone width), e.g. "5+" or "<3". */
    public function shortLabel(): string
    {
        return match ($this) {
            self::D => '<3',
            self::C => '3+',
            self::B => '5+',
            self::A => '6+',
            self::X => '7+',
            self::Y => '8+',
            self::Z => '9+',
        };
    }

    /** Screen reader label, e.g. "Safety rating B, 5.00 to 5.99". */
    public function ariaLabel(): string
    {
        return 'Safety rating '.$this->value.', '
            .number_format($this->min(), 2).' to '.number_format($this->max() - 0.01, 2);
    }

    /** Colour to use for plain text in this grade's colour. D is white, so it reads dark grey instead. */
    public function textColor(): string
    {
        return $this === self::D ? '#374151' : $this->color();
    }

    /** Values below zero fall into D and anything above the cap into Z, without throwing. */
    public static function fromRating(float $rating): self
    {
        foreach (array_reverse(self::cases()) as $grade) {
            if ($rating >= $grade->min()) {
                return $grade;
            }
        }

        return self::D;
    }

    /** The whole scale in ascending order (D to Z), for scale displays. */
    public static function scale(): array
    {
        return self::cases();
    }

    /** Legacy array shape used by the driver profile and list views. */
    public function toArray(): array
    {
        return [
            'grade' => $this->value,
            'color' => $this->color(),
            'text_color' => $this->textColor(),
            'min' => $this->min(),
            'max' => $this->max(),
        ];
    }

    /**
     * Letter and colour for whole-number requirement values, keyed by the number
     * as a string ('1' to '9'), for the admin race form previews.
     */
    public static function requirementMap(): array
    {
        $map = [];
        foreach (range(1, 9) as $n) {
            $grade = self::fromRating((float) $n);
            $map[(string) $n] = [$grade->value, $grade->color()];
        }

        return $map;
    }
}
