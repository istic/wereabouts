<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class VenueSheetFlagTest extends TestCase
{
    public function test_it_is_false_for_null(): void
    {
        $this->assertFalse(venue_sheet_flag(null));
    }

    public function test_it_is_false_for_an_empty_or_whitespace_only_string(): void
    {
        $this->assertFalse(venue_sheet_flag(''));
        $this->assertFalse(venue_sheet_flag('   '));
    }

    public function test_it_matches_the_default_yes_prefix_case_insensitively_and_ignoring_whitespace(): void
    {
        $this->assertTrue(venue_sheet_flag('Yes'));
        $this->assertTrue(venue_sheet_flag('  yes please  '));
        $this->assertTrue(venue_sheet_flag('Y'));
    }

    public function test_it_does_not_match_no_by_default(): void
    {
        $this->assertFalse(venue_sheet_flag('No'));
        $this->assertFalse(venue_sheet_flag('None'));
    }

    public function test_it_matches_additional_truthy_prefixes(): void
    {
        // "All spaces" is a real value used for the "Step free access" column,
        // which isn't a plain Yes/No field.
        $this->assertTrue(venue_sheet_flag('All spaces', ['y', 'all']));
        $this->assertFalse(venue_sheet_flag('Some spaces', ['y', 'all']));
    }
}
