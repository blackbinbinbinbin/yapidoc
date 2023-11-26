<?php
namespace Script;

class Installer
{
    public static function postInstall()
    {
        // Copy the yapidoc script to the vendor/bin directory.
        copy(__DIR__ . '/../../src/yapidoc', __DIR__ . '/../../bin/yapidoc');
        chmod(__DIR__ . '/../../bin/yapidoc', 0755);
    }
}