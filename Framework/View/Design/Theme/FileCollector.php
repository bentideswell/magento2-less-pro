<?php
/**
 *
 */
namespace FishPig\LessPro\Framework\View\Design\Theme;

class FileCollector
{
    /**
     *
     */
    private $files = [];

    /**
     *
     */
    public function __construct(
        \FishPig\LessPro\Framework\View\Design\Theme\PathProvider $themePathProvider
    ) {
        $this->themePathProvider = $themePathProvider;
    }

    /**
     *
     */
    public function getAll(\Magento\Framework\View\Asset\File $asset): array
    {
        $files = [];
        foreach ($this->themePathProvider->getAll($asset) as $path) {
            if (!isset($this->files[$path])) {
                $this->files[$path] = [];

                foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(BP . '/' . $path)) as $file) {
                    if (strpos($file->getPathname(), '/.git/') === false && preg_match('/\.less$/', $file->getFilename())) {
                        if ($content = trim(file_get_contents($file->getPathname()))) {
                            $this->files[$path][$file->getPathname()] = $content;
                        }
                    }
                }
            }

            $files = array_merge($files, $this->files[$path]);
        }

        return $files;
    }
}
