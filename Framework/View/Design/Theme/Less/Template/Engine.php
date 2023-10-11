<?php
/**
 *
 */
namespace FishPig\LessPro\Framework\View\Design\Theme\Less\Template;

class Engine
{
    /**
     *
     */
    private $lessVariableProvider = null;

    /**
     *
     */
    private $mediaQueryPrefixProvider = null;

    /**
     *
     */
    public function __construct(
        \FishPig\LessPro\Framework\View\Design\Theme\Less\VariableProvider $lessVariableProvider,
        \FishPig\LessPro\Framework\View\Design\Theme\Less\MediaQueryPrefixProvider $mediaQueryPrefixProvider
    ) {
        $this->lessVariableProvider = $lessVariableProvider;
        $this->mediaQueryPrefixProvider = $mediaQueryPrefixProvider;
    }

    /**
     *
     */
    public function renderFile(\Magento\Framework\View\File $file, array $variables = []): string
    {
        if (!is_file($file->getFilename())) {
            throw new \InvalidArgumentException(
                sprintf(
                    'File "%s" does not exist.',
                    $file->getFilename()
                )
            );
        }

        return $this->render(file_get_contents($file->getFilename()), $variables);
    }

    /**
     *
     */
    public function render(string $content, array $variables = []): string
    {
        // Expand any variables in the format:
        // //@spacing(some-variable, 20px)
        $content = $this->lessVariableProvider->expandVariables($content);

        $extractedVariables = [];
        // First lets extract any variable defaults and move to the top
        // This stops them from being repeated in each media query scope
        foreach ($this->lessVariableProvider->get($content) as $variable) {
            if (preg_match('/' . $variable . ':.*;/U', $content, $match)) {
                $content = str_replace($match[0], '', $content);
                $extractedVariables[$variable] = $match[0];
            }
        }

        // Now render the template
        $content = $this->renderTemplate($content, array_merge($variables, array_keys($extractedVariables)));

        return "//\n//\n//\n" . implode("\n", $extractedVariables) . "\n\n" . $content . "\n";
    }

    /**
     *
     */
    private function renderTemplate(string $template, array $variables)
    {
        // Variable prefixes used in template
        $variablePrefixesInTemplate = $this->lessVariableProvider->get($template, "\n", '//');

        // Try to find the indent. This makes things look nice. No one sees
        // it but it helps me sleep better at night
        $indents = [];
        foreach ($variablePrefixesInTemplate as $variablePrefix) {
            if (preg_match('/\n([\s]{0,})\/\/' . $variablePrefix . '/', $template, $match)) {
                $indents[$variablePrefix] = $match[1];
            }
        }

        // This stores each individual template for each media query
        $renderedTemplates = [];

        $mediaQueryPrefixes = $this->mediaQueryPrefixProvider->getAll();
        // Loop through each media query (including common) and try to generate template
        // Only generate template if variables are used in that scope
        foreach (array_merge(['common'], $mediaQueryPrefixes) as $mediaQuery) {
            // Copy the template so we can use the original again
            $scopedTemplate = $template;
            // This keeps track of if variables are set in this scope
            // If false then we can discard the template for this scope
            $scopeHasVariables = false;

            foreach ($variablePrefixesInTemplate as $variablePrefix) {
                if ($mediaQuery !== 'common') {
                    $targetPrefix = '@_' . $mediaQuery . '_' . ltrim($variablePrefix, '@');
                } else {
                    $targetPrefix = $variablePrefix;
                }

                $usedVariables = array_filter(
                    $variables,
                    function ($variable) use ($targetPrefix) {
                        return strpos($variable, $targetPrefix . '__') === 0;
                    }
                );

                // Reset replace value from previous loop
                $replace = '';
                if ($usedVariables) {
                    $scopeHasVariables = true;
                    if (count($usedVariables) > 1) {
                        // Ensure CSS rules are in alphabetical order
                        sort($usedVariables);
                    }
                    $replace = [];
                    foreach ($usedVariables as $variable) {
                        $rule = $this->lessVariableProvider->getCssRule($variable);
                        // Rule may have already been setup using .lib-css
                        // This can happen if file is going to be delivered via
                        // a module
                        if (strpos($template, $rule) === false) {
                            $replace[] = $rule;
                        }
                    }

                    // Fixes a bug that had duplicate rules
                    $replace = array_values(array_unique($replace));

                    $replace  = implode("\n" . ($indents[$variablePrefix] ?? ''), $replace);
                }

                $scopedTemplate = str_replace('//' . $variablePrefix . "\n", $replace . "\n", $scopedTemplate);
            }

            if ($scopeHasVariables) {
                // Clean up those blank lines with whitespace we may have created
                $scopedTemplate = preg_replace('/\n\s+\n/', "\n", $scopedTemplate);
                $renderedTemplates[$mediaQuery] = $scopedTemplate;
            }
        }

        // Add media query wrapper
        foreach ($renderedTemplates as $mediaQuery => $renderedTemplate) {
            // This fixes indentation when added to a media query
            // As we want everything intended by 4 spaces
            $renderedTemplate = str_replace("\n", "\n    ", "\n" . trim($renderedTemplate)) . "\n";

            if ($mediaQuery === 'common') {
                $renderedTemplates[$mediaQuery] = "& when (@media-common = true) {" . $renderedTemplate . "}";
            } else {
                $extremum = substr($mediaQuery, 0, 3);
                $screen = strtolower(substr($mediaQuery, 3));
                $renderedTemplates[$mediaQuery] = sprintf(
                    ".media-width(@extremum, @break) when (@extremum = '%s') and (@break = @screen__%s) {%s}",
                    $extremum,
                    $screen,
                    $renderedTemplate
                );
            }
        }

        $finalRenderedTemplate = implode("\n\n", $renderedTemplates);
        return $finalRenderedTemplate;
    }
}
