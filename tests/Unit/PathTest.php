<?php

namespace One23\PathNormalizer\Unit;

use PHPUnit\Framework\TestCase;
use One23\PathNormalizer\Path;
use One23\PathNormalizer;

class PathTest extends TestCase
{
    public function testCut() {
        $this->assertSame(
            "/bar/baz",
            Path::cut(['foo', 'bar', 'baz'], ['foo'])
        );

        $this->assertSame(
            "/bar/baz",
            Path::cut(['foo', 'bar', 'baz'], ['', 'foo'])
        );

        $this->assertSame(
            "/bar/baz",
            Path::cut(['', 'foo', 'bar', 'baz'], ['foo'])
        );
    }

    public function testAbsolute() {
        $this->assertSame(
            "/foo/bar/baz",
            Path::absolute(['foo', 'bar', 'baz'])
        );

        $this->assertSame(
            "/foo/bar/baz",
            Path::absolute(['/foo', 'bar', 'baz'])
        );

        $this->assertSame(
            "/baz",
            Path::absolute(['/foo', '..', 'baz'])
        );
    }

    public function testArgumentVariations()
    {
        $this->assertPath(['foo', 'bar', 'baz'], Path::normalize(['foo', 'bar', 'baz']));
        $this->assertPath(['foo', 'bar', 'baz'], Path::normalize(['foo', 'bar', 'baz']));
        $this->assertPath(['foo', 'bar', 'baz'], Path::normalize(['foo', 'bar/baz']));
        $this->assertPath(['foo', 'bar', 'baz'], Path::normalize(['foo', 'bar/baz']));
    }

    public function testEmptyPathArray()
    {
        $this->expectException(PathNormalizer\Exception::class);
        Path::normalize([]);
    }

    public function testEmptyAbsolutePaths()
    {
        $this->assertPath(['', ''], Path::normalize(['/', '']));
        $this->assertPath(['', ''], Path::normalize(['\\', '']));
        $this->assertPath(['C:', ''], Path::normalize(['C:\\', '']));
        $this->assertPath(['C:', ''], Path::normalize(['C:', '']));
    }

    public function testAbsolutePaths()
    {
        $this->assertPath(['', 'foo', 'bar'], Path::normalize(['/foo', 'bar']));
        $this->assertPath(['C:', 'foo', 'bar'], Path::normalize(['C:/foo', 'bar']));
    }

    public function testRelativePaths()
    {
        $this->assertPath(['', ''], Path::normalize(['', '/', '//']));
        $this->assertPath(['foo', 'bar'], Path::normalize(['foo', '/bar']));
    }

    public function testDirectorySeparators()
    {
        $this->assertPath(['', 'foo', 'bar', 'baz'], Path::normalize('\foo\bar\baz'));
        $this->assertPath(['', 'foo', 'bar', 'baz'], Path::normalize('/foo/bar/baz'));
    }

    public function testInvalidColon()
    {
        $this->expectException(PathNormalizer\Exception::class);
        Path::normalize('foo', 'C:\bar');
    }

    public function testSpecialDirectories()
    {
        $this->assertPath(['', 'foo', 'baz'], Path::normalize('/foo/bar/../baz'));
        $this->assertPath(['', 'foo', 'bar', 'baz'], Path::normalize('/foo/bar/./baz'));
    }

    public function testRelativeBacktracking()
    {
        $this->assertPath([''], Path::normalize(['foo/bar', '..', '/..']));
        $this->assertPath(['..', 'baz'], Path::normalize(['foo/bar', '..', '/../../', 'baz']));
    }

    public function testAbsoluteBacktracking()
    {
        $this->assertPath(['', ''], Path::normalize(['/foo/bar', '..', '/..']));
        $this->assertPath(['', 'baz'], Path::normalize(['/foo/bar', '..', '/../../', 'baz']));
    }

    public function testWindowsAbsolutePaths()
    {
        $this->assertPath(['C:', 'baz'], Path::normalize(['C:/foo/bar', '..', '/../../', 'baz']));
    }

    public function testNormalization()
    {
        $this->assertPath(['foo', 'bar'], Path::normalize('foo/bar'));
    }

    public function testDriveNormalization()
    {
        $this->assertPath([strstr(getcwd(), DIRECTORY_SEPARATOR, true), 'foo', 'bar'], Path::normalize('/foo/bar'));
    }

    public function testEmptyPath()
    {
        $this->assertSame('', Path::normalize(''));
    }

    public function testZeroAsEmptyPath()
    {
        $this->assertSame('0', Path::normalize('0'));
    }

    private function assertPath(array $expected, $actual)
    {
        $this->assertSame(
            implode(DIRECTORY_SEPARATOR, $expected),
            $actual
        );
    }
}