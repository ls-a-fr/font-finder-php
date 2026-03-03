<?php

namespace Lsa\Font\Finder\Decoders;

use Exception;
use Lsa\Font\Finder\Contracts\FontDecoder;
use Lsa\Font\Finder\Platform\SystemInformation;
use Lsa\Font\Finder\Platform\Windows;
use RuntimeException;
use Symfony\Component\Process\Process;

class WebOpenFontFormat2 implements FontDecoder
{
    public static function extractFontMeta(string $raw, string $filename): array
    {
        $raw = self::decodeWoff2($raw);
        return TrueTypeFont::extractFontMeta($raw, $filename);
    }

    private static function decodeWoff2(string $raw): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'lff');
        \file_put_contents($tempFile, $raw);

        if(SystemInformation::getCurrent()->isWindows()) {
            $process = new Process([
                'cmd.exe',
                '/c',
                self::getWoff2Executable(),
                $tempFile
            ]);
        } else {
            $process = new Process([
                self::getWoff2Executable(),
                $tempFile
            ]);
        }
        
        $process->run();

        if ($process->isSuccessful() !== true) {
            // Debug BSD
            error_log('WOFF2 exit code: ' . $process->getExitCode());
            error_log('WOFF2 STDERR: ' . $process->getErrorOutput());
            error_log('WOFF2 STDOUT: ' . $process->getOutput());
            // End Debug BSD
            throw new RuntimeException('Could not execute woff2_decompress');
        }

        $ttfFile = $tempFile . '.ttf';
        if(\str_contains($tempFile, '.')) {
            $ttfFileParts = explode('.', $tempFile);
            \array_pop($ttfFileParts);
            $ttfFileParts[] = 'ttf';
            $ttfFile = implode('.', $ttfFileParts);
        }

        if(!\file_exists($ttfFile)) {
            throw new RuntimeException('Could not generate (or find) ttf file');
        }
        return \file_get_contents($ttfFile);
    }

    private static function getWoff2Executable() : string
    {
        $sysInfo = Windows::getSystemInformation();
        $path = realpath(implode(\DIRECTORY_SEPARATOR, [
            __DIR__,
            '..',
            '..',
            'deps',
            $sysInfo->getValue(SystemInformation::FORMAT_DEPS),
            'woff2_decompress.exe'
        ]));
        if($path === false) {
            throw new RuntimeException('Util woff2_decompress.exe could not be found, check your install');
        }
        return $path;
    }

}