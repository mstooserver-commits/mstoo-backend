<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class BlogServiceTest extends TestCase
{
    public function test_sanitize_html_keeps_safe_markup()
    {
        $html = sanitize_html('<h2>Title</h2><p>Safe <strong>text</strong></p>');
        $this->assertStringContainsString('<h2>Title</h2>', $html);
        $this->assertStringContainsString('<strong>text</strong>', $html);
    }
}
