<?php
/**
 *
 */
namespace FishPig\LessPro\Framework\Css\PreProcessor\LessPro;

class RuleProvider implements \Magento\Framework\View\Asset\PreProcessorInterface
{
    /**
     *
     */
    const COMMAND = '@inject_rules';

    /**
     *
     */
    private $propertyMap = [
        'background' => [
            'background-(color|image|position|repeat|size|width)'
        ],
        'border' => [
            'border-(top|right|bottom|left)',
        ],
        'flex-container' => [
            'flex-(wrap)',
            'justify-(content|items)',
            'align-(content|items)',
        ],
        'flex-item' => [
            'flex',
            'flex-(shrink|grow)'
        ],
        'font' => [
            'font-(family|size|weight)',
            'line-height'
        ],
        'margin' => [
            'margin-(top|right|bottom|left)'
        ],
        'padding' => [
            'padding-(top|right|bottom|left)'
        ],
        'position' => [
            'top',
            'right',
            'bottom',
            'left'
        ]
    ];

    /**
     *
     */
    private $commands = [];

    /**
     *
     */
    public function __construct(
        \FishPig\LessPro\Framework\View\Design\Theme\FileCollector $themeFileCollector,
        array $propertyMap = []
    ) {
        $this->themeFileCollector = $themeFileCollector;
        $this->propertyMap = $this->expandPropertyMap(
            array_merge($this->propertyMap, $propertyMap)
        );
    }

    /**
     *
     */
    public function process(\Magento\Framework\View\Asset\PreProcessor\Chain $chain)
    {
        $content = $chain->getContent();
        if (!($commands = $this->extractCommands($content))) {
            return;
        }

        foreach ($commands as $command) {
            $content = str_replace(
                $command['original_command'],
                implode("\n" . $command['padding'], $command['rules']),
                $content
            );
        }

        $chain->setContent($content);
    }

    /**
     *
     */
    public function collectRules(\Magento\Framework\View\Asset\PreProcessor\Chain $chain): void
    {
        foreach ($this->themeFileCollector->getAll($chain->getAsset()) as $file => $content) {
            $this->extractCommands($content);
        }
    }

    /**
     *
     */
    private function extractCommands(string $content): array
    {
        $hash = md5(trim($content));

        if (!isset($this->commands[$hash])) {
            $this->commands[$hash] = [];

            if (preg_match_all('/\n(\s*)(\/\/' . self::COMMAND . '\(([^,]+),\s*([^\)]+)\)[;]*)/', $content, $matches)) {
                foreach ($matches[0] as $it => $command) {
                    $variablePrefix = '@' . ltrim($matches[3][$it], '@');

                    $command = [
                        'original_command' => trim($command),
                        'padding' => $matches[1][$it],
                        'variable_prefix' => $variablePrefix,
                        'properties' => strtolower(preg_replace('/\s+/', '', $matches[4][$it])),
                        'variables' => [],
                        'rules' => []
                    ];

                    foreach ($this->getExpandedProperties($command['properties']) as $property) {
                        $command['variables'][] = $variable = $command['variable_prefix'] . '__' . $property;

                        $command['rules'][] = sprintf(
                            '.lib-css(%s, %s);',
                            $property,
                            $variable
                        );
                    }

                    $this->commands[$hash][] = $command;
                }
            }
        }

        return $this->commands[$hash];
    }

    /**
     *
     */
    public function getInjectedVariables(): array
    {
        $variables = [];
        foreach ($this->commands as $hash => $commands) {
            foreach ($commands as $command) {
                foreach ($command['variables'] as $variable) {
                    $variables[] = $variable;
                }
            }
        }

        return $variables;
    }

    /**
     *
     */
    private function getExpandedProperties(string $props): array
    {
        $mapped = [];
        $props = strtolower(preg_replace('/\s+/', '', $props));

        if ($props === '*') {
            $props = array_keys($this->propertyMap);
        } else {
            $props = explode(',', $props);
        }

        foreach ($props as $prop) {
            $mapped[] = $prop;
            if (isset($this->propertyMap[$prop])) {
                if (is_array($this->propertyMap[$prop])) {
                    $mapped = array_merge($mapped, $this->propertyMap[$prop]);
                }
            }
        }

        return $mapped;
    }

    /**
     * Expands property like padding-(top|bottom) into
     * padding-top
     * padding-bottom
     */
    private function expandPropertyMap(array $propertyMap): array
    {
        foreach ($propertyMap as $key => $values) {
            if (is_array($values)) {
                foreach ($values as $it => $value) {
                    if (strpos($value, '(') !== false) {
                        //\(([^\)]+)\)(.*)$
                        if (!preg_match('/^(.*)\(([^\)]+)\)(.*)$/', $value, $match)) {
                            throw new \InvalidArgumentException(
                                sprintf(
                                    'Invalid property (%s) cannot be expanded.',
                                    $value
                                )
                            );
                        }
                        $newProps = [];
                        foreach (explode('|', $match[2]) as $dynamic) {
                            $newProps[] = $match[1] . $dynamic . $match[3];
                        }

                        $propertyMap[$key][$it] = $newProps;
                    }
                }
            }
        }

        foreach ($propertyMap as $key => $values) {
            if (is_array($values)) {
                $updated = [];
                foreach ($values as $value) {
                    if (is_array($value)) {
                        $updated = array_merge($updated, $value);
                    } else {
                        $updated[] = $value;
                    }
                }

                $propertyMap[$key] = $updated;
            }
        }

        return $propertyMap;
    }
}
