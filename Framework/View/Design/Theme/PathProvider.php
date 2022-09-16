<?php
/**
 *
 */
namespace FishPig\LessPro\Framework\View\Design\Theme;

class PathProvider
{
    /**
     *
     */
    private $paths = [];

    /**
     *
     */
    public function __construct(
        \Magento\Framework\View\Design\Theme\ThemeProviderInterface $themeProvider
    ) {
        $this->themeProvider = $themeProvider;
    }

    /**
     *
     */
    public function getAll(\Magento\Framework\View\Asset\File $asset): array
    {
        $context = $asset->getContext();
        $themeKey = $context->getAreaCode() . ' /' . $context->getThemePath();

        if (!isset($this->paths[$themeKey])) {
            $this->paths[$themeKey] = [];

            if ($context instanceof \Magento\Framework\View\Asset\File\FallbackContext) {
                $theme = $this->themeProvider->getThemeByFullPath(
                    $context->getAreaCode() . '/' . $context->getThemePath()
                );

                do {
                    $this->paths[$themeKey][] = 'app/design/' . $theme->getFullPath();
                    $theme = $theme->getParentTheme();
                } while ($theme && count($this->paths[$themeKey]) < 5);
                // This includes a safety limit to prevent recursion if theme parent
                // is not set correctly
            }
       }

       return $this->paths[$themeKey];
    }
}
