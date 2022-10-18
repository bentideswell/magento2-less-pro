<?php
/**
 *
 */
namespace FishPig\LessPro\Framework\View\Design\Theme\Less;

class TemplateRenderer
{
    /**
     *
     */
    const VARIABLE_NAME = '@_fishpig-template';

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
    public function render(string $content, string $theme): ?string
    {
        if (strpos($content, self::VARIABLE_NAME) === false) {
            return null;
        }

        // Expand any variables in the format:
        // //@spacing(some-variable, 20px)
        $content = $this->lessVariableProvider->expandVariables($content);

        $extractedVariables = [];
        // First lets extract any variable defaults and move to the top
        foreach ($this->lessVariableProvider->get($content) as $variable) {
            if (preg_match('/' . $variable . ':.*;/U', $content, $match)) {
                $content = str_replace($match[0], '', $content);
                $extractedVariables[] = $match[0];
            }
        }

        // This is the pattern to match the opening tag of a template
        $pattern = $this->getTemplateStartRegex();
        $buffer = $content;
        // Infinite loop protection
        $safety = 10;
        // Extract and render template tags
        while (--$safety > 0 && preg_match($pattern, $buffer, $matches)) {
            // Remove all content before the start tag
            $buffer = substr($buffer, strpos($buffer, $matches[0]));

            // Try to get the template from the current position
            // Move through brackets until level gets back to 0
            if (false === ($template = $this->getTemplateTag($buffer))) {
                throw new \Exception('Invalid syntax in LESS file.');
            }

            $buffer = str_replace($template, '', $buffer);

            $content = str_replace(
                $template,
                $this->renderTemplate(
                    $template,
                    $this->lessVariableProvider->getByThemePath($theme)
                ),
                $content
            );
        }

        return "//\n//\n//\n" . implode("\n", $extractedVariables) . "\n\n" . $content;
    }

    /**
     *
     */
    private function renderTemplate(string $template, array $userDefinedVariables)
    {
        // Strip opening tag
        $template = preg_replace($this->getTemplateStartRegex(), '', $template);
        // Strip closing bracket
        $template = preg_replace('/\}$/', '', rtrim($template));

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
                    $userDefinedVariables,
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
                        $replace[] = $this->lessVariableProvider->getCssRule($variable);
                    }
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

    private function getTemplateTag(string $content)
    {
        $safety = 10;
        $buffer = $content;
        $length = 0;
        $level = 0;
        while ($buffer && $safety > 0) {
            $opener = strpos($buffer, '{');
            $closer = strpos($buffer, '}');
            $neitherExist = $opener === false && $closer === false;
            $bothExist = $opener !== false && $closer !== false;

            if ($neitherExist) {
                // Invalid syntax
                return false;
            }

            if ($closer === false || ($bothExist && $opener < $closer)) {
                $level++;
                $length += $opener+1;
                $buffer = substr($buffer, $opener+1);
            } else {
                $level--;
                $length += $closer+1;
                $buffer = substr($buffer, $closer+1);

                if ($level === 0) {
                    return substr($content, 0, $length);
                }
            }
        }

        return false;
    }

    /**
     *
     */
    private function getTemplateStartRegex(): string
    {
        return '/&\s{0,}when\s{0,}\(' . self::VARIABLE_NAME . '\s{0,}=\s{0,}true\)\s{0,}\{/U';
    }
}
