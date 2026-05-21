<?php

namespace Tests\Unit;

use Tests\TestCase;

class SecureUploadTest extends TestCase {
    
    /**
     * Test filename sanitization rules.
     */
    public function testFilenameSanitization() {
        $filenames = [
            '../../etc/passwd' => 'passwd',
            'my ebook version 1.2!.pdf' => 'my_ebook_version_1.2_.pdf',
            'photo-collage_2026.png' => 'photo-collage_2026.png',
            'malicious<>:"/\|?*script.sh' => '____script.sh',
            '..' => '',
            '.' => '',
        ];

        foreach ($filenames as $input => $expected) {
            $cleanName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', basename($input));
            $cleanName = trim($cleanName, '. ');
            
            // Expected sanitized name mapping
            if ($input === '..' || $input === '.') {
                $this->assertEquals('', $cleanName);
            } else {
                $this->assertEquals($expected, $cleanName);
            }
        }
    }

    /**
     * Test the blocked extensions list (Defense in Depth denylist).
     */
    public function testBlockedExtensions() {
        $blockedExtensions = [
            'php', 'phtml', 'php5', 'php7', 'phps', 'phar', 
            'sh', 'cgi', 'pl', 'py', 'asp', 'aspx', 'jsp', 
            'exe', 'bat', 'cmd'
        ];

        $testFiles = [
            'test.php' => true,
            'test.phtml' => true,
            'script.sh' => true,
            'malicious.EXE' => true,
            'EBOOK.PDF' => false,
            'manual.zip' => false,
            'archive.TAR.GZ' => false,
        ];

        foreach ($testFiles as $file => $shouldBeBlocked) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $isBlocked = in_array($ext, $blockedExtensions);
            $this->assertEquals($shouldBeBlocked, $isBlocked, "File {$file} blocking failed.");
        }
    }
}
