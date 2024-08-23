<?php

namespace One23\PathNormalizer;

class Path implements \Stringable
{
    protected string $separator = DIRECTORY_SEPARATOR;

    protected array $parts = [];

    protected ?string $file = null;

    protected bool $isAbsolute = false;

    protected ?string $drive = null;

    public static function normalize(
        array|string $path,
        string $separator = null
    ): string {
        return (new static($path, $separator))
            ->toString();
    }

    public static function absolute(
        array|string $path,
        string $separator = null
    ): string {
        return (new static($path, $separator))
            ->setAbsolute()
            ->toString();
    }

    public function __construct(array|string $path, string $separator = null)
    {
        if (! is_null($separator)) {
            $this->setSeparator($separator);
        }

        if (is_array($path) && empty($path)) {
            throw new Exception('Must provide at least one `path`: empty array');
        }

        $parts = $this->parts($path);

        //

        $this->detectFile($parts);
        $this->detectAbsolute($parts, $path);

        $this->parts = $parts;
    }

    public function setAbsolute(bool $absolute = true): static {
        $this->isAbsolute = $absolute;

        return $this;
    }

    public function setSeparator(string $separator): static {
        if (! in_array($separator, [
            '/', '\\',
        ])) {
            throw new Exception("directory `separator` only `/` on `\\`: `{$separator}`");
        }

        $this->separator = $separator;

        return $this;
    }

    public function setDrive(string $drive = null): static {
        if (is_null($drive)) {
            $this->drive = null;
        }

        if (!preg_match('#^[a-z]$#', $drive)) {
            throw new Exception("`drive` can be only [a-z]{1}: `{$drive}`");
        }

        $this->drive = $drive;

        return $this;
    }

    protected function isValidPath(string $path): bool
    {
        if (str_contains($path, ':')) {
            throw new Exception("Invalid path character `:`: `{$path}`");
        }

        if (
            $path === ''
            || $path === '.'
            || $path === '..'
        ) {
            return false;
        }

        return true;
    }

    protected function resolveParent(array &$res): void
    {
        $cnt = count($res);

        if (
            ! $this->isAbsolute
            && (
                ! $cnt
                || $res[$cnt - 1] === '..'
            )
        ) {
            $res[] = '..';

            return;
        }

        if ($cnt && $res[$cnt - 1] !== '..') {
            array_pop($res);
        }
    }

    protected function path(): array {
        $res = [];

        foreach ($this->parts as $part) {
            if ($part === '..') {
                $this->resolveParent($res);
            } elseif ($this->isValidPath($part)) {
                $res[] = $part;
            }
        }

        return $res;
    }

    protected function detectAbsolute(array &$parts, string|array $path): void
    {
        if (
            $parts[0] === ''
        ) {
            $this->isAbsolute = true;
            $this->drive = null;

            array_shift($parts);

            return;
        }

        if (preg_match('#^([a-z]):$#i', $parts[0], $match)) {
            $this->isAbsolute = true;
            $this->drive = $match[1];

            array_shift($parts);

            return;
        }

        $this->isAbsolute = false;
        $this->drive = null;
    }

    protected function detectFile(array &$parts): void
    {
        $end = end($parts);

        if (
            is_string($end)
            && $end !== ''
            && $end !== '.'
            && $end !== '..'
            && !str_contains($end, ':')
        ) {
            array_pop($parts);

            $this->file = $end;
        } else {
            $this->file = null;
        }
    }

    protected function part2array(string $path): array {
        $path = trim($path);

        if ($path === '') {
            return [];
        }

        return array_map(function($val) {
            return trim($val);
        }, preg_split('#[/\\\\]+#', $path));
    }

    protected function parts(array|string $path): array
    {
        $items = is_array($path)
            ? $path
            : $this->part2array($path);

        $res = [];
        foreach ($items as $val) {
            $res[] = $val;
        }

        if (empty($res)) {
            return [];
        }

        $str = preg_replace(
            '#[/\\\\]+#',
            $this->separator,
            implode($this->separator, $res)
        );

        if ($str === '') {
            return [];
        }

        return explode(
            $this->separator,
            $str
        );
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function toString(): string
    {
        return $this->getPath();
    }

    public function getPath(): string
    {
        return implode(
            $this->separator,
            $this->toArray()
        );
    }

    public function toArray(): array
    {
        $res = array_filter([
            ...$this->path(),

            $this->getFile(),
        ], function ($val) {
            return ! is_null($val);
        });

        if (empty($res)) {
            $res[] = '';
        }

        if ($this->isAbsolute) {
            array_unshift($res,
                $this->drive
                    ? $this->drive.':'
                    : '');
        }

        return $res;
    }

    public function getFile(): ?string
    {
        return $this->file;
    }

    public function isFile(): bool
    {
        return (bool) $this->file;
    }

    public function crc(): string
    {
        return md5(
            $this->getPath()
        );
    }

    public function isHidden(): bool
    {
        $file = $this->getFile();
        if (! $file) {
            return false;
        }

        if (preg_match('#^(._~)#', $file)) {
            return true;
        }

        return false;
    }

    public function getFileName(): ?string
    {
        $file = $this->getFile();
        if (is_null($file)) {
            return null;
        }

        if (preg_match('#(.*)(\.([a-z0-9]+))$#', $file, $match)) {
            return $match[1];
        }

        return $file;
    }

    public function getFileExt(): ?string
    {
        $file = $this->getFile();
        if (is_null($file)) {
            return null;
        }

        if (preg_match('#(.*)(\.([a-z0-9]+))$#', $file, $match)) {
            return $match[3];
        }

        return null;
    }

    public function getDirectory(): string
    {
        return
            (
                $this->isAbsolute
                    ? $this->separator
                    : '').
            implode($this->separator, $this->path());
    }

    public static function cut(array|string $haystack, array|string $needle, string $separator = null) {
        $haystack = static::absolute($haystack, $separator);
        $needle = static::absolute($needle, $separator);

        if (!str_starts_with($haystack, $needle)) {
            throw new Exception("`haystack` not contain `separator`: `{$haystack}` / `{$separator}`");
        }

        return mb_substr($haystack, mb_strlen($needle));
    }
}