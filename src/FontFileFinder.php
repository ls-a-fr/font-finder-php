<?php

declare(strict_types=1);

namespace Lsa\Font\Finder;

use DirectoryIterator;
use Lsa\Font\Finder\Exceptions\ConfigurationException;
use Lsa\Font\Finder\Platform\Bsd;
use Lsa\Font\Finder\Platform\Darwin;
use Lsa\Font\Finder\Platform\Linux;
use Lsa\Font\Finder\Platform\Solaris;
use Lsa\Font\Finder\Platform\Windows;

/**
 * Main class to find fonts and extract metadata from them
 */
class FontFileFinder
{
    /**
     * Flag: Auto detect fonts in system
     */
    private bool $autoDetect = false;

    /**
     * Found (or given) directories
     *
     * @var list<non-empty-string>
     */
    private array $directories = [];

    /**
     * Exceptions, meaning patterns or exact filenames to exclude
     *
     * @var array{patterns:list<non-empty-string>,exact:list<non-empty-string>}
     */
    private array $exceptions = [
        'patterns' => [],
        'exact' => [],
    ];

    /**
     * Errors raised while finding fonts
     *
     * @var list<string>
     */
    private array $errors = [];

    /**
     * Flag: Should or should not output errors in stdout
     */
    private bool $silent = false;

    /**
     * Flag: Should collect performance metrics in FontDecoder
     */
    private bool $enableMetrics = false;

    /**
     * Decoder class, if one need to change decoding behavior
     */
    private string $decoderClass = FontDecoder::class;

    private function __construct()
    {
        // Private constructor
    }

    /**
     * Allows to change decoder class. Default is FontDecoder in this package.
     *
     * @param  class-string<FontDecoder>  $className
     *
     * @throws ConfigurationException Supplied class name must be a subclass of FontDecoder
     */
    public function setDecoderClass(string $className): FontFileFinder
    {
        /**
         * On public APIs, never trust documentation types
         *
         * @phpstan-ignore function.alreadyNarrowedType, identical.alwaysFalse
         */
        if (\is_subclass_of($className, FontDecoder::class) === false) {
            throw new ConfigurationException('Supplied class name must be a subclass of FontDecoder');
        }
        $this->decoderClass = $className;

        return $this;
    }

    /**
     * Exclude files or folders based on patterns
     *
     * @param  non-empty-string|list<non-empty-string>  $pattern  Pattern(s) to add to exception list
     */
    public function except(string|array $pattern): FontFileFinder
    {
        if (\is_string($pattern) === false) {
            $this->exceptions['patterns'] = \array_merge($this->exceptions['patterns'], $pattern);
        } else {
            $this->exceptions['patterns'][] = $pattern;
        }

        return $this;
    }

    /**
     * Exclude files based on exact filename
     *
     * @param  non-empty-string|list<non-empty-string>  $filename  File name(s) to add to exception list
     */
    public function exceptExact(string|array $filename): FontFileFinder
    {
        if (\is_string($filename) === false) {
            $this->exceptions['exact'] = \array_merge($this->exceptions['exact'], $filename);
        } else {
            $this->exceptions['exact'][] = $filename;
        }

        return $this;
    }

    /**
     * Enable performance metrics. Find operation will be a little slower.
     */
    public function enableMetrics(): FontFileFinder
    {
        $this->enableMetrics = true;

        return $this;
    }

    /**
     * Disable performance metrics. Default is disabled.
     */
    public function disbleMetrics(): FontFileFinder
    {
        $this->enableMetrics = false;

        return $this;
    }

    /**
     * Silence error output to stdout. Default is not silent.
     */
    public function silent(): FontFileFinder
    {
        $this->silent = true;

        return $this;
    }

    /**
     * Creates a new FontFileFinder instance
     */
    public static function init(): FontFileFinder
    {
        return new self();
    }

    /**
     * Add system fonts to discovery.
     */
    public function addSystemFonts(): FontFileFinder
    {
        $this->autoDetect = true;

        return $this;
    }

    /**
     * Adds a directory to discovery. To also add subdirectories, use `addDirectoryRecursive`.
     *
     * @param  non-empty-string  $directory  Directory to add
     *
     * @throws ConfigurationException Non-existent directory or file
     */
    public function addDirectory(string $directory): FontFileFinder
    {
        if (\file_exists($directory) === false) {
            throw new ConfigurationException('Directory '.$directory.' does not seem to exist');
        }
        if (\is_dir($directory) === false) {
            throw new ConfigurationException('Path '.$directory.' is not a directory');
        }
        $this->directories = \array_values(\array_unique([
            ...$this->directories,
            $directory,
        ]));

        return $this;
    }

    /**
     * Adds a directory to discovery, recursively. To ignore subdirectories, use `addDirectory`.
     *
     * @param  non-empty-string  $directory  Directory to add
     */
    public function addDirectoryRecursive(string $directory): FontFileFinder
    {
        $this->directories = \array_values(\array_unique([
            ...$this->directories,
            ...$this->doAddDirectoryRecursive($directory),
            $directory,
        ]));

        return $this;
    }

    /**
     * Performs lookup for `addDirectoryRecursive` and add directories to discovery.
     *
     * @param  non-empty-string  $directory  Directory to lookup
     * @return list<non-empty-string> Found directories
     */
    private function doAddDirectoryRecursive(string $directory): array
    {
        $directory = rtrim($directory, \DIRECTORY_SEPARATOR);
        $it = new DirectoryIterator($directory);

        $directories = [];
        foreach ($it as $fileInfo) {
            if ($fileInfo->isDot() === true) {
                continue;
            }
            if ($fileInfo->isDir() === true) {
                $directories = array_merge(
                    $directories,
                    [$directory.\DIRECTORY_SEPARATOR.$fileInfo->getFilename()],
                    $this->doAddDirectoryRecursive(
                        $directory.\DIRECTORY_SEPARATOR.$fileInfo->getFilename()
                    )
                );
            }
        }

        return $directories;
    }

    /**
     * Get values from FontFileFinder.
     * If specified, `$params` allows to get more or less data.
     *
     * @param  non-empty-string[]  ...$params  Data to include
     * @return array<string, array<Font>>|(int|array<string|int, Font[]|string>)[]
     *
     * @example Example 1: only retrieve fonts
     * ```php
     * // Fonts variable will contain found fonts
     * $fonts = FontFileFinder::init()
     *   ->addSystemFonts()
     *   ->get();
     * ```
     * @example Example 2: retrieve errors and count, not fonts
     * ```php
     * [$errors, $count] = FontFileFinder::init()
     *   ->addSystemFonts()
     *   ->get('errors', 'count');
     * ```
     * @example Example 3: Same as example 2, but in a different order
     * ```php
     * [$count, $errors] = FontFileFinder::init()
     *   ->addSystemFonts()
     *   ->get('count', 'errors');
     * ```
     * @example Example 4: retrieve errors and fonts
     * ```php
     * [$errors, $fonts] = FontFileFinder::init()
     *   ->addSystemFonts()
     *   ->get('errors', 'fonts');
     * ```
     */
    public function get(string ...$params): array
    {
        $fontFiles = [];
        if ($this->autoDetect === true) {
            $fontFiles = \array_merge($fontFiles, self::getSystemFontFiles());
        }
        foreach ($this->directories as $directory) {
            $fontFiles = \array_merge($fontFiles, self::getFontsInDirectory($directory));
        }

        $fonts = $this->extractFontsMetadata($fontFiles, $this->exceptions);

        if (empty($params) === true) {
            return $fonts;
        }

        $ret = [];
        if (\in_array('count', $params) === true) {
            $cnt = 0;
            foreach ($fonts as $derivatives) {
                $cnt += count($derivatives);
            }
            /**
             * Params is an index-based array, it can never be a string.
             * Prior call to in_array also prevents key to be false.
             *
             * @var int $key
             */
            $key = \array_search('count', $params);
            $ret[$key] = $cnt;
        }
        if (\in_array('errors', $params) === true) {
            /**
             * Params is an index-based array, it can never be a string.
             * Prior call to in_array also prevents key to be false.
             *
             * @var int $key
             */
            $key = \array_search('errors', $params);
            $ret[$key] = $this->errors;
        }
        if (\in_array('fonts', $params) === true) {
            /**
             * Params is an index-based array, it can never be a string.
             * Prior call to in_array also prevents key to be false.
             *
             * @var int $key
             */
            $key = \array_search('fonts', $params);
            $ret[$key] = $fonts;
        }

        return $ret;
    }

    /**
     * Same as `get`, but returns only fonts with a configuration array instead of chain calls.
     *
     * @param  array{autoDetect?:bool,directories?:list<non-empty-string|array{path:non-empty-string,recursive?:bool}>}  $config  Configuration array
     * @return array<string, array<Font>>
     *
     * @example Example 1: only retrieve fonts from operating system
     * ```php
     * // Fonts variable will contain found fonts
     * $fonts = FontFileFinder::load([
     *      'autoDetect' => true
     * ]);
     * ```
     * @example Example 2: only retrieve fonts from specified directories
     * ```php
     * // Fonts variable will contain found fonts
     * $fonts = FontFileFinder::load([
     *      'directories' => [
     *          '/usr/local/...',
     *          '/home/user/...'
     *      ]
     * ]);
     * ```
     * @example Example 3: only retrieve fonts from specified directories, recursively for first item
     * ```php
     * // Fonts variable will contain found fonts
     * $fonts = FontFileFinder::load([
     *      'directories' => [
     *          [
     *              'recursive' => true,
     *              'path' => '/usr/local/...'
     *          ],
     *          '/home/user/...'
     *      ]
     * ]);
     * ```
     *
     * @throws ConfigurationException Invalid directory supplied
     */
    public static function load(array $config): array
    {
        $autoDetect = ($config['autoDetect'] ?? false);
        $directories = ($config['directories'] ?? []);

        $inst = new self();
        if ($autoDetect === true) {
            $inst->addSystemFonts();
        }
        foreach ($directories as $directory) {
            /**
             * On public APIs, never trust documentation types
             *
             * @phpstan-ignore notIdentical.alwaysTrue
             */
            if (\is_string($directory) === true && $directory !== '') {
                $inst->addDirectory($directory);

                continue;
            }
            /**
             * On public APIs, never trust documentation types
             *
             * @phpstan-ignore function.alreadyNarrowedType, identical.alwaysTrue
             */
            if (\is_array($directory) === true && ($directory['recursive'] ?? false) === true) {
                $inst->addDirectoryRecursive($directory['path']);

                continue;
            }

            throw new ConfigurationException('Invalid directory supplied');
        }

        /**
         * Method `load` never returns errors and count
         *
         * @var array<string, array<Font>> $fonts
         */
        $fonts = $inst->get();

        return $fonts;
    }

    /**
     * Retrieves system fonts
     *
     * @return array<string, Font[]> Fonts, aggregated by font names
     */
    public static function getSystemFonts(): array
    {
        $fontFiles = self::getSystemFontFiles();

        return self::init()->extractFontsMetadata($fontFiles, [
            'patterns' => [],
            'exact' => [],
        ]);
    }

    /**
     * Retrieves every file in specified directory.
     * No filter is done here: filter will be handled by FontDecoder.
     *
     * @param  string  $directory  Target directory
     * @return list<non-empty-string> Absolute path to files
     */
    private static function getFontsInDirectory(string $directory): array
    {
        $fontFiles = [];
        $iterator = new DirectoryIterator($directory);
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isDot() === true) {
                continue;
            }

            if ($fileInfo->isDir() === true) {
                continue;
            }

            $fontFiles[] = $directory.\DIRECTORY_SEPARATOR.$fileInfo->getFilename();
        }

        return $fontFiles;
    }

    /**
     * Extract metadata from found fonts.
     *
     * @param  list<non-empty-string>  $fontFiles  Candidate files
     * @param  array{patterns:list<non-empty-string>,exact:list<non-empty-string>}  $exceptions  Exceptions
     * @return array<string, Font[]> Fonts, aggregated by font names
     */
    private function extractFontsMetadata(array $fontFiles, array $exceptions): array
    {
        if ($this->enableMetrics === true) {
            $this->decoderClass::enablePerformanceMetrics();
        }

        /**
         * Fonts are aggregated by font name
         *
         * @var array<string,Font[]>
         */
        $fonts = [];
        foreach ($fontFiles as $fontFile) {
            $fontFileRealPath = \realpath($fontFile);
            if($fontFileRealPath === false) {
                $str = 'Error decoding font '.$fontFile.': File does not exist';
                $this->errors[] = $str;
                if ($this->silent === false) {
                    \error_log($str);
                }
                continue;
            }
            // Manage exceptions
            if (self::shouldIgnoreFile($fontFileRealPath, $exceptions) === true) {
                continue;
            }

            // Extract metadata
            try {
                $foundFonts = $this->decoderClass::extractFontMeta($fontFileRealPath);
                foreach ($foundFonts as $font) {
                    if (isset($fonts[$font->name]) === false) {
                        $fonts[$font->name] = [];
                    }
                    $fonts[$font->name][] = $font;
                }
            } catch (ConfigurationException $e) {
                $str = 'Error decoding font '.$fontFileRealPath.': '.$e->getMessage();
                $this->errors[] = $str;
                if ($this->silent === false) {
                    // That's exactly the point, to allow people to see errors to stderr
                    // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
                    \error_log($str);
                }

                continue;
            }
        }

        // Cleanup
        // array_unique, because a lot of .fon files register many fonts with size difference, or
        // screen differences. We do not want to fill our array with "equivalent" fonts.
        // array_values, because array_unique keep keys.
        //
        // Note that this does not remove fonts with different file names.
        foreach (array_keys($fonts) as $familyName) {
            /**
             * Found fonts
             *
             * @var Font[] $uniqueFonts
             */
            $uniqueFonts = array_unique($fonts[$familyName], \SORT_REGULAR);
            /**
             * Font keys are string.
             *
             * @var string $familyName
             */
            $fonts[$familyName] = array_values($uniqueFonts);
        }

        return $fonts;
    }

    /**
     * Helper method to decide whether we should ignore this font file or not, based on declared exceptions.
     *
     * @param  string  $fontFile  Absolute font file path
     * @param  array{patterns:list<non-empty-string>,exact:list<non-empty-string>}  $exceptions  Exceptions
     * @return bool True if we should ignore this file, false otherwise.
     */
    protected static function shouldIgnoreFile(string $fontFile, array $exceptions): bool
    {
        foreach ($exceptions['exact'] as $filename) {
            if (\str_ends_with($fontFile, \DIRECTORY_SEPARATOR.$filename) === true) {
                return true;
            }
        }
        foreach ($exceptions['patterns'] as $exceptionPattern) {
            if (\preg_match($exceptionPattern, $fontFile) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrives system fonts' absolute path
     *
     * @return list<non-empty-string>
     */
    protected static function getSystemFontFiles()
    {
        $fontFiles = [];
        $directories = self::getFontDirectories();
        foreach ($directories as $directory) {
            $fontFiles = \array_merge($fontFiles, self::getFontsInDirectory($directory));
        }

        return $fontFiles;
    }

    /**
     * Lookup for font directories.
     * Every system has different font directories: `C:\WINDOWS\FONTS` for Windows, `/Library/Fonts/` for macOS,
     * to name a few.
     * Based on current Operating System.
     *
     * @return list<non-empty-string>
     */
    protected static function getFontDirectories(): array
    {
        switch (PHP_OS_FAMILY) {
            case 'Windows':
                return Windows::getFontDirectories();
            case 'Darwin':
                return Darwin::getFontDirectories();
            case 'Linux':
                return Linux::getFontDirectories();
            case 'Solaris':
                return Solaris::getFontDirectories();
            case 'BSD':
                return Bsd::getFontDirectories();
            default:
                return [];
        }
    }
}
