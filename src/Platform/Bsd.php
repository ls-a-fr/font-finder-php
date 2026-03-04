<?php

namespace Lsa\Font\Finder\Platform;

use Lsa\Font\Finder\Contracts\FontPlatform;
use Symfony\Component\Process\Process;

class Bsd implements FontPlatform {
    public static function getFontDirectories(): array
    {
        $homeDir = getenv('HOME');
        return [
            $homeDir . \DIRECTORY_SEPARATOR . '.fonts',      // user
            $homeDir . \DIRECTORY_SEPARATOR . '.local/share/fonts',
            "/usr/local/share/fonts",                        // local
            "/usr/local/lib/X11/fonts",                      // X11 local
            "/usr/share/fonts",                              // system
            "/usr/X11R6/lib/X11/fonts"                       // X11 R6 legacy
        ];
    }

    public static function getSystemInformation(): SystemInformation
    {
        $sub = strtolower(php_uname('s'));
        switch($sub) {
            case 'freebsd':
            case 'openbsd':
                break;
            default:
                // FreeBSD is the most common out there, hopefully this unknown
                // architecture is compatible with FreeBSD.
                \trigger_error('Could not detect BSD system, fallback to freebsd');
                $sub = 'freebsd';
        }
        $arch = strtolower(php_uname('m'));

        // Debug BSD
        echo "ARCH IS: " . $arch;
        // End Debug BSD

        switch($arch) {
            case 'amd64':
                return new SystemInformation(SystemInformation::OS_BSD, $sub, 'amd64');
            case 'aarch64': // FreeBSD
            case 'arm64':   // OpenBSD
                return new SystemInformation(SystemInformation::OS_BSD, $sub, 'arm64');
            default:
                \trigger_error('Could not detect BSD arch, fallback to amd64');
                return new SystemInformation(SystemInformation::OS_BSD, $sub, 'amd64');
        }
    }
}