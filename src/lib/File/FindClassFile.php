<?php
namespace Lib\File;

class FindClassFile
{
    private $basePath;

    public function __construct($path)
    {
        $this->basePath = realpath($path);
        if ($this->basePath === false && !is_dir($this->basePath)) {
            throw new \InvalidArgumentException('Invalid path provided. Must be a valid directory or file path.');
        }
    }

    public function getFiles()
    {
        $files = [];

        if (is_file($this->basePath)) {
            // 如果是文件，直接返回该文件路径
            $files[] = $this->basePath;
        } else {
            // 如果是目录，使用递归获取目录下所有文件
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->basePath),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }
}