<?php
/**
 *
 */
namespace FishPig\LessPro\Framework\View\Design\Theme;

class VariableProvider
{
    /**
     *
     */
    private $variables = [];

    /**
     *
     */
    private $themePath = null;

    /**
     *
     */
    private $prohibtedVariableNames = [
        '@import',
        '@magento_import',
        '@media-common',
        '@extremum',
        '@break',
        '@screen__xs',
        '@screen__s',
        '@screen__m',
        '@screen__l',
        '@0',
        '@100',
        '@200',
        '@300',
        '@400',
        '@500',
        '@600',
        '@700',
        '@800',
        '@900'
    ];

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
        $key = $asset->getPath();
        if (!isset($this->variables[$key])) {
           $this->variables[$key] = [];

            if ($themePaths = $this->getThemePaths($asset)) {
                $data = trim(implode("\n", $this->collectFiles($themePaths)));

                // Clean comments
                $data = "\n" . str_replace("\r", '', $data);
                $data = preg_replace("/\n\/\/[^\n]*\n/sU", "\n", $data);
                $data = preg_replace("/\n\/\/[^\n]*\n/sU", "\n", $data);
                $data = preg_replace("/\n\/\/[^\n]*\n/sU", "\n", $data);

                $vars = [];

                if (preg_match_all('/@[a-zA-Z0-9_\-]+/', $data, $directMatches)) {
                    $vars = array_unique($directMatches[0]);
                }

                if (preg_match_all('/@\{[a-zA-Z0-9_\-]+\}/', $data, $directMatches)) {
                    $vars = array_merge(
                        $vars,
                        array_map(
                            function ($variable) {
                                return str_replace(['{', '}'], '', $variable);
                            },
                            array_unique($directMatches[0])
                        )
                    );
                }

                $this->variables[$key] = array_values(
                    array_diff(array_unique($vars), $this->prohibtedVariableNames)
                );
            }
       }

       return $this->variables[$key];
    }

    /**
     *
     */
    private function collectFiles(array $themePaths): array
    {
        $files = [];
        foreach ($themePaths as $themePath) {
            foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($themePath)) as $file) {
                if (strpos($file->getPathname(), '/.git/') === false && preg_match('/\.less$/', $file->getFilename())) {
                    $files[$file->getPathname()] = file_get_contents($file->getPathname());
                }
            }
        }
        return $files;
    }

    /**
     *
     */
    private function getThemePaths(\Magento\Framework\View\Asset\File $asset): array
    {
        $context = $asset->getContext();
        $themePaths = [];

        if ($context instanceof \Magento\Framework\View\Asset\File\FallbackContext) {
            $theme = $this->themeProvider->getThemeByFullPath(
                $context->getAreaCode() . '/' . $context->getThemePath()
            );

            do {
                $themePaths[] = BP . '/app/design/' . $theme->getFullPath();
                $theme = $theme->getParentTheme();
            } while ($theme && count($themePaths) < 5);
            // This includes a safety limit to prevent recursion if theme parent
            // is not set correctly
        }

        return $themePaths;
    }
}
