#!/usr/bin/env php
<?php
require __DIR__ . '/vendor/autoload.php';

// This file allows to test woff2_decompress, and does some checks on file discovery on the way
// Runned in pipeline to ensure woff2_decompress is safe to use on a bare system

use Lsa\Font\Finder\Font;
use Lsa\Font\Finder\FontFileFinder;

// Gather fonts with woff and woff2.
// Fonts are provided courtesy of https://software.sil.org, thanks for their fonts!
$fonts = [
    FontFileFinder::init()
        ->addDirectoryRecursive(__DIR__ . '/woff_samples')
        ->get(),
    FontFileFinder::init()
        ->addDirectoryRecursive(__DIR__ . '/woff2_samples')
        ->get()
];

// Utility method to compute full path, as Font class stores an absolute file name
function createFullPath(string $format, string $fontName, string $fileName)
{
    return realpath(str_replace('/', DIRECTORY_SEPARATOR, implode(DIRECTORY_SEPARATOR, [
        __DIR__,
        $format . '_samples',
        $fontName,
        $fileName . '.' . $format
    ])));
}

// Utility method to get expected output from FontFileFinder.
// Note: if you change test fonts, you MUST also change this function!
function getExpected(string $format)
{
    return [
        'Andika' => [
            new Font([
                'filename' => createFullPath($format, 'Andika', 'Andika-Bold'),
                'weight' => 700,
                'italic' => false,
                'bold' => true,
                'name' => 'Andika'
            ]),
            new Font([
                'filename' => createFullPath($format, 'Andika', 'Andika-BoldItalic'),
                'weight' => 700,
                'italic' => true,
                'bold' => true,
                'name' => 'Andika'
            ]),
            new Font([
                'filename' => createFullPath($format, 'Andika', 'Andika-Italic'),
                'weight' => 400,
                'italic' => true,
                'bold' => false,
                'name' => 'Andika'
            ]),
            new Font([
                'filename' => createFullPath($format, 'Andika', 'Andika-Regular'),
                'weight' => 400,
                'italic' => false,
                'bold' => false,
                'name' => 'Andika'
            ])
        ],
        'Andika Medium' => [
            new Font([
                'filename' => createFullPath($format, 'Andika', 'Andika-Medium'),
                'weight' => 500,
                'italic' => false,
                'bold' => false,
                'name' => 'Andika Medium'
            ]),
            new Font([
                'filename' => createFullPath($format, 'Andika', 'Andika-MediumItalic'),
                'weight' => 500,
                'italic' => true,
                'bold' => false,
                'name' => 'Andika Medium'
            ]),
        ],
        'Andika SemiBold' => [
            new Font([
                'filename' => createFullPath($format, 'Andika', 'Andika-SemiBold'),
                'weight' => 600,
                'italic' => false,
                'bold' => false,
                'name' => 'Andika SemiBold'
            ]),
            new Font([
                'filename' => createFullPath($format, 'Andika', 'Andika-SemiBoldItalic'),
                'weight' => 600,
                'italic' => true,
                'bold' => false,
                'name' => 'Andika SemiBold'
            ])
        ],
        'Charis' => [
            new Font([
                'filename' => createFullPath($format, 'Charis', 'Charis-Bold'),
                'weight' => 700,
                'italic' => false,
                'bold' => true,
                'name' => 'Charis'
            ]),
            new Font([
                'filename' => createFullPath($format, 'Charis', 'Charis-BoldItalic'),
                'weight' => 700,
                'italic' => true,
                'bold' => true,
                'name' => 'Charis'
            ]),
            new Font([
                'filename' => createFullPath($format, 'Charis', 'Charis-Italic'),
                'weight' => 400,
                'italic' => true,
                'bold' => false,
                'name' => 'Charis'
            ]),
            new Font([
                'filename' => createFullPath($format, 'Charis', 'Charis-Regular'),
                'weight' => 400,
                'italic' => false,
                'bold' => false,
                'name' => 'Charis'
            ])
        ],
        'Charis Medium' => [
            new Font([
                'filename' => createFullPath($format, 'Charis', 'Charis-Medium'),
                'weight' => 500,
                'italic' => false,
                'bold' => false,
                'name' => 'Charis Medium'
            ]),
            new Font([
                'filename' => createFullPath($format, 'Charis', 'Charis-MediumItalic'),
                'weight' => 500,
                'italic' => true,
                'bold' => false,
                'name' => 'Charis Medium'
            ]),
        ],
        'Charis SemiBold' => [
            new Font([
                'filename' => createFullPath($format, 'Charis', 'Charis-SemiBold'),
                'weight' => 600,
                'italic' => false,
                'bold' => false,
                'name' => 'Charis SemiBold'
            ]),
            new Font([
                'filename' => createFullPath($format, 'Charis', 'Charis-SemiBoldItalic'),
                'weight' => 600,
                'italic' => true,
                'bold' => false,
                'name' => 'Charis SemiBold'
            ])
        ],
    ];
}

// Debug BSD
var_dump(__DIR__);
var_dump(is_dir(__DIR__ . '/woff_samples'));
var_dump(glob(__DIR__ . '/*'));

// Actual check: structure,  number of elements, order, everything.
foreach ([getExpected('woff'), getExpected('woff2')] as $i => $fontCollection) {
    // Special BSD operation:
    // Prevents failure in BSD filesystems: file order in not consistent with other OSes
    foreach(array_keys($fontCollection) as $key) {
        usort($fontCollection[$key], fn($a, $b) => $a->filename <=> $b->filename);
    }
    foreach(array_keys($fonts[$i]) as $key) {
        usort($fonts[$i][$key], fn($a, $b) => $a->filename <=> $b->filename);
    }

    //Debug BSD
    var_dump($fontCollection, $fonts[$i]);

    // Testing
    assert(array_keys($fontCollection) === array_keys($fonts[$i]));

    foreach ($fonts[$i] as $fontName => $fontDerivatives) {
        foreach ($fontDerivatives as $j => $derivative) {
            assert($derivative == $fontCollection[$fontName][$j]);
        }
    }
}