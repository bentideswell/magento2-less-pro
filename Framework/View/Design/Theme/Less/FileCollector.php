<?php
/**
 *
 */
namespace FishPig\LessPro\Framework\View\Design\Theme\Less;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\View\Design\ThemeInterface;

class FileCollector
{
    /**
     *
     */
    private $themeProvider = null;

    /**
     *
     */
    private $fileFactory = null;

    /**
     *
     */
    private $componentRegistrar = null;

    /**
     *
     */
    private $cache = [];

    /**
     *
     */
    public function __construct(
        \Magento\Framework\View\Design\Theme\ThemeProviderInterface $themeProvider,
        \Magento\Framework\View\File\Factory $fileFactory,
        ComponentRegistrarInterface $componentRegistrar
    ) {
        $this->themeProvider = $themeProvider;
        $this->fileFactory = $fileFactory;
        $this->componentRegistrar = $componentRegistrar;
    }

    /**
     *
     */
    public function getFiles(string $path, string $extension = '.less'): array
    {
        if (substr($path, 0, 1) !== '/') {
            $path = BP . '/' . $path;
        }

        $cacheKey = $path . '::' . $extension;

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $this->cache[$cacheKey] = [];
        $fileIterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path)
        );

        foreach($fileIterator as $file) {
            if (strpos($file->getPathname(), '/.git/') !== false) {
                continue;
            }

            if (!$extension || $this->stringEndsWith($file->getPathname(), $extension)) {
                $this->cache[$cacheKey][] = $this->fileFactory->create(
                    $file->getPathname(),
                    null,
                    null
                );
            }
        }

        return $this->cache[$cacheKey];
    }

    /**
     *
     */
    public function getFilesByTheme($theme, string $extension = '.less'): array
    {
        if (false === ($theme instanceof ThemeInterface)) {
            $theme = $this->themeProvider->getThemeByFullPath($theme);
        }

        $files = [];
        foreach ($theme->getInheritedThemes() as $themeBuffer) {
            $files = array_merge(
                $files,
                $this->getFiles(
                    $this->componentRegistrar->getPath(
                        ComponentRegistrar::THEME,
                        $themeBuffer->getFullPath()
                    ),
                    $extension
                )
            );
        }

        return $files;
    }

    /**
     *
     */
    private function stringEndsWith(string $str, string $endsWith): bool
    {
        return strlen($str) >= strlen($endsWith) && substr($str, -strlen($endsWith)) === $endsWith;
    }
}
