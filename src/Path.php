<?php

namespace One23\PathNormalizer;

class Path implements \Stringable
{
    protected string $separator = DIRECTORY_SEPARATOR;

    protected array $path = [];

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

    public function __construct(array|string $path, string $separator = null)
    {
        if (! is_null($separator)) {
            if (! in_array($separator, [
                '/', '\\',
            ])) {
                throw new Exception('directory separator only `/` on `\\`');
            } else {
                $this->separator = $separator;
            }
        }

        if (is_array($path) && empty($path)) {
            throw new Exception('Must provide at least one `path`');
        }

        $parts = $this->parts($path);

        //

        $this->setFile($parts);
        $this->setAbsolute($parts, $path);
        $this->resolve($parts);

        $this->path = $parts;
    }

    protected function isValidPath(string $path): bool
    {
        if (str_contains($path, ':')) {
            throw new Exception('Invalid path character ":"');
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

    protected function resolve(array &$parts): void
    {
        $res = [];
        foreach ($parts as $path) {
            if ($path === '..') {
                $this->resolveParent($res);
            } elseif ($this->isValidPath($path)) {
                $res[] = $path;
            }
        }

        $parts = $res;
    }

    protected function setAbsolute(array &$parts, string|array $path): void
    {
        if (
            $parts[0] === ''
        ) {
            $this->isAbsolute = true;
            $this->drive = null;

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

    protected function setFile(array &$parts): void
    {
        $end = end($parts);

        if (
            $end
            && $end !== '.'
            && $end !== '..'
            && !preg_match('#^[a-z]:$#i', $end)
        ) {
            array_pop($parts);

            $this->file = $end;
        } else {
            $this->file = null;
        }
    }

    protected function parts(array|string $path): array
    {
        if (is_string($path)) {
            $path = trim($path);
            if ($path === '') {
                return [];
            }

            return preg_split('#[/\\\\]+#', trim($path));
        }

        $res = [];
        foreach ($path as $val) {
            $res = [
                ...$res,
                ...$this->parts($val),
            ];
        }

        return $res;
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
            ...$this->path,

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
            implode($this->separator, $this->path);
    }
}