<?php

namespace Beelab\TestBundle\File;

use Symfony\Component\HttpFoundation\File\UploadedFile;

final class FileInjector
{
    public static function getFile(string $file, string $data, string $ext, string $mime, bool $fixture = false): UploadedFile
    {
        $name = 'file_'.$file.'.'.$ext;
        $path = \tempnam(\sys_get_temp_dir(), 'sf_test_').$name;
        \file_put_contents($path, \str_starts_with($mime, 'text') ? $data : \base64_decode($data));

        return new UploadedFile($path, $name, $mime, null, $fixture);
    }

    public static function getImageFile(string $file = '0', bool $fixture = false): UploadedFile
    {
        $data = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADElEQVQI12P4//8/AAX+Av7czFnnAAAAAElFTkSuQmCC';

        return self::getFile($file, $data, 'png', 'image/png', $fixture);
    }

    public static function getPdfFile(string $file = '0', bool $fixture = false): UploadedFile
    {
        $data = <<<'EOF'
            JVBERi0xLjEKJcKlwrHDqwoKMSAwIG9iagogIDw8IC9UeXBlIC9DYXRhbG9nCiAgICAgL1BhZ2VzIDIgMCBSCiAgPj4KZW5kb2JqCgoyIDAgb2JqCiAgP
            DwgL1R5cGUgL1BhZ2VzCiAgICAgL0tpZHMgWzMgMCBSXQogICAgIC9Db3VudCAxCiAgICAgL01lZGlhQm94IFswIDAgMzAwIDE0NF0KICA+PgplbmRvYm
            oKCjMgMCBvYmoKICA8PCAgL1R5cGUgL1BhZ2UKICAgICAgL1BhcmVudCAyIDAgUgogICAgICAvUmVzb3VyY2VzCiAgICAgICA8PCAvRm9udAogICAgICA
            gICAgIDw8IC9GMQogICAgICAgICAgICAgICA8PCAvVHlwZSAvRm9udAogICAgICAgICAgICAgICAgICAvU3VidHlwZSAvVHlwZTEKICAgICAgICAgICAg
            ICAgICAgL0Jhc2VGb250IC9UaW1lcy1Sb21hbgogICAgICAgICAgICAgICA+PgogICAgICAgICAgID4+CiAgICAgICA+PgogICAgICAvQ29udGVudHMgN
            CAwIFIKICA+PgplbmRvYmoKCjQgMCBvYmoKICA8PCAvTGVuZ3RoIDU1ID4+CnN0cmVhbQogIEJUCiAgICAvRjEgMTggVGYKICAgIDAgMCBUZAogICAgKE
            hlbGxvIFdvcmxkKSBUagogIEVUCmVuZHN0cmVhbQplbmRvYmoKCnhyZWYKMCA1CjAwMDAwMDAwMDAgNjU1MzUgZiAKMDAwMDAwMDAxOCAwMDAwMCBuIAo
            wMDAwMDAwMDc3IDAwMDAwIG4gCjAwMDAwMDAxNzggMDAwMDAgbiAKMDAwMDAwMDQ1NyAwMDAwMCBuIAp0cmFpbGVyCiAgPDwgIC9Sb290IDEgMCBSCiAg
            ICAgIC9TaXplIDUKICA+PgpzdGFydHhyZWYKNTY1CiUlRU9GCg==
            EOF;

        return self::getFile($file, $data, 'pdf', 'application/pdf', $fixture);
    }

    public static function getTxtFile(string $file = '0', bool $fixture = false): UploadedFile
    {
        $data = 'Lorem ipsum dolor sit amet';

        return self::getFile($file, $data, 'txt', 'text/plain', $fixture);
    }

    public static function getZipFile(string $file = '0', bool $fixture = false): UploadedFile
    {
        $data = <<<'EOF'
            UEsDBAoAAgAAAM5RjEVOGigMAgAAAAIAAAAFABwAaC50eHRVVAkAA/OxilTzsYpUdXgLAAEE6AMAAARkAAAAaApQSwECHgMKAAIAAADOUYxF
            ThooDAIAAAACAAAABQAYAAAAAAABAAAApIEAAAAAaC50eHRVVAUAA/OxilR1eAsAAQToAwAABGQAAABQSwUGAAAAAAEAAQBLAAAAQQAAAAAA
            EOF;

        return self::getFile($file, $data, 'zip', 'application/zip', $fixture);
    }
}
