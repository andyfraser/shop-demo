<?php

namespace Tests\Unit;

use Tests\TestCase;

require_once __DIR__ . '/../../src/Helpers.php';

class HelpersTest extends TestCase {
    public function testH() {
        $this->assertEquals('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', h('<script>alert("xss")</script>'));
        $this->assertEquals('John &amp; Doe', h('John & Doe'));
    }

    public function testSlugify() {
        $this->assertEquals('hello-world', slugify('Hello World'));
        $this->assertEquals('some-product-name', slugify('  Some Product Name  '));
        $this->assertEquals('iphone-13-pro', slugify('iPhone 13 Pro!!!'));
        $this->assertEquals('a-b-c', slugify('a b c'));
        $this->assertEquals('multiple-spaces-and-dashes', slugify('multiple   spaces  and---dashes'));
    }
}
