<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AutoLinkTest extends TestCase
{
    public function test_it_returns_an_empty_string_for_null_input(): void
    {
        $this->assertSame('', auto_link(null));
    }

    public function test_it_links_a_plain_url(): void
    {
        $this->assertSame(
            '<a href="http://example.com">http://example.com</a>',
            auto_link('http://example.com')
        );
    }

    public function test_it_links_a_www_only_url_with_an_http_prefix(): void
    {
        $this->assertSame(
            '<a href="http://www.example.com">www.example.com</a>',
            auto_link('www.example.com')
        );
    }

    public function test_it_adds_target_blank_and_rel_noopener_when_popup_is_true(): void
    {
        $this->assertSame(
            '<a href="http://example.com" target="_blank" rel="noopener noreferrer">http://example.com</a>',
            auto_link('http://example.com', popup: true)
        );
    }

    public function test_it_does_not_link_dangerous_url_schemes(): void
    {
        $this->assertSame(
            'javascript://alert(1)',
            auto_link('javascript://alert(1)')
        );
    }

    public function test_it_links_multiple_urls_in_one_string(): void
    {
        $this->assertSame(
            'See <a href="http://a.example">http://a.example</a> and <a href="http://b.example">http://b.example</a>.',
            auto_link('See http://a.example and http://b.example.', popup: false)
        );
    }

    public function test_it_escapes_surrounding_text(): void
    {
        $this->assertSame(
            '&lt;script&gt;alert(1)&lt;/script&gt;',
            auto_link('<script>alert(1)</script>')
        );
    }

    public function test_it_escapes_quotes_in_a_url_so_they_cannot_break_out_of_the_href_attribute(): void
    {
        $result = auto_link('http://example.com/"onmouseover="alert');

        $this->assertStringNotContainsString('"onmouseover="alert"', $result);
        $this->assertStringContainsString('&quot;onmouseover=&quot;alert', $result);
    }
}
