<?php

namespace Lsa\Font\Finder\Decoders;

use Lsa\Font\Finder\BinaryReader;
use Lsa\Font\Finder\Contracts\FontDecoder;
use Lsa\Font\Finder\Font;
use RuntimeException;

class TrueTypeFont implements FontDecoder
{
    public static function extractFontMeta(string $raw, string $filename): array
    {
        $reader = new BinaryReader($raw);
        $reader->read(4); // scaler type
        $numTables = $reader->readUInt16();
        // Debug BSD
        error_log("TTF numTables=$numTables");
        // End Debug BSD
        $reader->read(6); // searchRange etc.

        $tables = [];

        for ($i = 0; $i < $numTables; $i++) {
            try {
                $tag = $reader->read(4);
                $reader->readUInt32(); // checksum
                $offset = $reader->readUInt32();
                $length = $reader->readUInt32();

                $tables[$tag] = [$offset, $length];
            } catch (RuntimeException $e) {
                continue;
            }
        }
        // Debug BSD
        error_log("TTF tables found: " . implode(',', array_keys($tables)));
        // End Debug BSD

        $family = 'Unknown';
        $weight = 400;
        $italic = false;
        $bold = false;

        /* -------- OS/2 -------- */
        if (isset($tables['OS/2'])) {
            [$offset,] = $tables['OS/2'];
            $reader->seek($offset + 4); // skip version + xAvgCharWidth
            $weight = $reader->readUInt16();
            $reader->seek($offset + 62);
            $fsSelection = $reader->readUInt16();
            $italic = ($fsSelection & 0x01) !== 0;
            $bold   = ($fsSelection & 0x20) !== 0;
        }

        /* -------- head -------- */ else if (isset($tables['head'])) {
            [$offset,] = $tables['head'];
            $reader->seek($offset + 44);
            $macStyle = $reader->readUInt16();
            $italic = ($macStyle & 0x0002) !== 0;
            $bold   = ($macStyle & 0x0001) !== 0;
        }

        /* -------- name -------- */
        if (isset($tables['name'])) {
            [$offset,] = $tables['name'];
            $reader->seek($offset);

            $reader->readUInt16(); // format
            $count = $reader->readUInt16();
            $stringOffset = $reader->readUInt16();

            $recordsStart = $offset + 6;
            $storageStart = $offset + $stringOffset;

            for ($i = 0; $i < $count; $i++) {
                $reader->seek($recordsStart + ($i * 12));

                $platformID = $reader->readUInt16();
                $encodingID = $reader->readUInt16();
                $languageID = $reader->readUInt16();
                $nameID     = $reader->readUInt16();
                $length     = $reader->readUInt16();
                $offsetStr  = $reader->readUInt16();

                if (in_array($nameID, [1, 2, 16, 17])) {

                    $reader->seek($storageStart + $offsetStr);
                    $rawString = $reader->read($length);

                    // UTF-16BE ?
                    if ($platformID === 3) {
                        $rawString = mb_convert_encoding($rawString, 'UTF-8', 'UTF-16BE');
                    }

                    $value = trim($rawString);

                    if ($nameID === 16 && $value !== '') {
                        $family = $value;
                        break;
                    } elseif ($nameID === 1 && $family === 'Unknown') {
                        $family = $value;
                        break;
                    }
                }
            }
        }

        return [
            new Font([
                'name' => $family,
                'filename' => $filename,
                'weight' => $weight,
                'italic' => $italic,
                'bold' => $bold
            ])
        ];
    }
}
