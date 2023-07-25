# Normalize path

Todo

## Usage

```php
use One23\PathNormalizer\Path;

echo Path::normalize('foo/bar') . PHP_EOL; // outputs 'foo/bar'
echo Path::normalize(['foo', 'bar']) . PHP_EOL; // outputs 'foo/bar'

// The join method accepts multiple arguments or a single array
echo Path::normalize(['foo', 'bar', 'baz']) . PHP_EOL;   // outputs 'foo/bar/baz'

// The '.' and '..' directory references will be resolved in the paths
echo Path::normalize('foo/./bar/../baz') . PHP_EOL;     // outputs 'foo/baz'
echo Path::normalize(['foo/./', 'bar', '../baz']) . PHP_EOL; // outputs 'foo/baz'

// Only the first path can denote an absolute path in the join method
echo Path::normalize(['/foo', '/bar/baz']) . PHP_EOL;     // outputs '/foo/bar/baz'
echo Path::normalize(['foo', '/bar']) . PHP_EOL;          // outputs 'foo/bar'
echo Path::normalize(['foo', '../bar', 'baz']) . PHP_EOL; // outputs 'bar/baz'
echo Path::normalize(['', '/bar', 'baz']) . PHP_EOL;      // outputs '/bar/baz'

// Relative paths can start with a '..', but absolute paths cannot
echo Path::normalize(['/foo', '../../bar', 'baz']) . PHP_EOL; // outputs '/bar/baz'
echo Path::normalize(['foo', '../../bar', 'baz']) . PHP_EOL;  // outputs '../bar/baz'

// Empty paths will result in a '.'
echo Path::normalize(['foo/..']) . PHP_EOL; // outputs blank
echo Path::normalize(['foo', 'bar', '../..']) . PHP_EOL; // outputs blank
```