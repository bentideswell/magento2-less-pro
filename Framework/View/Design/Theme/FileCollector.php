<?php
/**
 *
 */
namespace FishPig\LessPro\Framework\View\Design\Theme;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\View\Design\ThemeInterface;

class FileCollector implements \Magento\Framework\View\File\CollectorInterface
{
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
    public function getFiles(ThemeInterface $theme, $filePath)
    {
        $files = [];
        foreach ($theme->getInheritedThemes() as $themeBuffer) {
            $fullThemePath = $this->componentRegistrar->getPath(
                ComponentRegistrar::THEME,
                $themeBuffer->getFullPath()
            );

            $fileIterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($fullThemePath)
            );

            foreach($fileIterator as $file) {
                if (strpos($file->getPathname(), '/.git/') !== false) {
                    continue;
                }

                if (!$filePath || $filePath === $file->getExtension())  {
                    $files[] = $this->fileFactory->create(
                        $file->getPathname(),
                        null,
                        $theme
                    );
                }
            }
        }

        return $files;
    }

    /**
     *
     */
    public function getFilesByThemePath($themePath, string $filePath): array
    {
        $theme = is_object($themePath) ? $theme : $this->themeProvider->getThemeByFullPath($themePath);
        return $theme->getFullPath() ? $this->getFiles($theme, $filePath) : [];
    }
}
