<?php
/**
 *
 */
namespace FishPig\LessPro\Framework\View\Design\Theme\Less;

class VariableProvider
{
    /**
     *
     */
    private $ignoreList = [
        '@import',
        '@magento_import',
        '@media-common',
        '@media-target',
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
        \FishPig\LessPro\Framework\View\Design\Theme\FileCollector $fileCollector,
        \FishPig\LessPro\Framework\View\Design\Theme\Less\MediaQueryPrefixProvider $mediaQueryPrefixProvider
    ) {
        $this->fileCollector = $fileCollector;
        $this->mediaQueryPrefixProvider = $mediaQueryPrefixProvider;
    }

    /**
     *
     */
    public function getByTheme(\Magento\Framework\View\Design\ThemeInterface $theme): array
    {
        return $this->getByThemePath($theme->getFullPath());
    }

    /**
     *
     */
    public function getByThemePath(string $theme): array
    {
        $themeFiles = $this->fileCollector->getFilesByThemePath($theme, 'less');
        $variables = [];
        foreach ($themeFiles as $file) {
            $variables = array_merge($variables, $this->getByFilename($file->getFileName()));
        }
        return $variables;
    }

    /**
     *
     */
    public function getByFilename(string $filename)
    {
        return $this->get(file_get_contents($filename));
    }

    /**
     *
     */
    public function get(string $content, $delimiter = ':'): array
    {
        $content = $this->expandVariables($content);

        if (preg_match_all('/(@[a-zA-Z0-9_\-]+)' . preg_quote($delimiter, '/') . '/', $content, $matches)) {
            return array_diff($matches[1], $this->ignoreList);
        }

        return [];
    }

    /**
     *
     */
    public function getCssRule(string $variable): string
    {
        // Strip off media query
        if (preg_match('/^@(_(min|max)[A-Z]+_)/', $variable, $match)) {
            $mediaPrefix = $match[0];
            $commonVariable = '@' . substr($variable, strlen($mediaPrefix));
        } else {
            $commonVariable = $variable;
        }

        if (strpos($commonVariable, '__') === false) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Variable "%s" does not have a property separator (__).',
                    $variable
                )
            );
        }

        list($prefix, $property) = explode('__', $commonVariable);

        return sprintf('.lib-css(%s, %s);', $property, $variable);
    }

    /**
     *
     */
    public function testCssRuleGeneration(?array $ruleMap = null): void
    {
        if ($ruleMap === null) {
            $ruleMap = [
                '@products-grid__display' => '.lib-css(display, @products-grid__display);',
                '@products-grid__text-align' => '.lib-css(text-align, @products-grid__text-align);',
                '@products-grid--hover__text-decoration' => '.lib-css(text-decoration, @products-grid--hover__text-decoration);',
                '@_minM_products-grid__color' => '.lib-css(color, @_minM_products-grid__color);',
                '@_minM_nav-li0--hover-ul0__display' => '.lib-css(display, @_minM_nav-li0--hover-ul0__display);'
            ];
        }

        foreach ($ruleMap as $variable => $expected) {
            if (($actual = $this->getCssRule($variable)) !== $expected) {
                throw new \RuntimeException(
                    sprintf(
                        'CSS rule generation failed. Input = "%s", Expected = "%s", Actual = "%s"',
                        $variable,
                        $expected,
                        $actual
                    )
                );
            }
        }
    }

    /**
     *
     */
    public function expandVariables(string $content): string
    {
        if (strpos($content, '@spacing') === false) {
            return $content;
        }

        if (preg_match_all('/\/\/@spacing\(([^\)]+)\)/', $content, $matches)) {
            foreach ($matches[0] as $it => $instruction) {
                $variableName = ltrim($matches[1][$it], '@ ');
                $defaultValue = null;

                if (($pos = strpos($variableName, ',')) !== false) {
                    $defaultValue = trim(substr($variableName, $pos+1));
                    $variableName = substr($variableName, 0, $pos);
                }

                $newVariables = [];

                if ($defaultValue !== null) {
                    $newVariables[] = sprintf(
                        '@%s: %s;',
                        $variableName,
                        $defaultValue
                    );
                }

                foreach ($this->mediaQueryPrefixProvider->getAll() as $mediaPrefix) {
                    $newVariables[] = sprintf(
                        '@_%s_%s: @_%s_spacing;',
                        $mediaPrefix,
                        $variableName,
                        $mediaPrefix
                    );
                }

                $content = str_replace($instruction, implode("\n", $newVariables) . "\n", $content);
            }
        }

        return $content;
    }
}
