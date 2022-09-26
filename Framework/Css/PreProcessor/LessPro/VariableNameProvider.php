<?php
/**
 *
 */
namespace FishPig\LessPro\Framework\Css\PreProcessor\LessPro;

class VariableNameProvider implements \Magento\Framework\View\Asset\PreProcessorInterface
{
    /**
     * @const string
     */
    const FILENAME = '_variables-init.less';

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
        \FishPig\LessPro\Framework\Css\PreProcessor\LessPro\RuleProvider $ruleProvider,
        \FishPig\LessPro\Framework\View\Design\Theme\FileCollector $themeFileCollector
    ) {
        $this->ruleProvider = $ruleProvider;
        $this->themeFileCollector = $themeFileCollector;
    }

    /**
     *
     */
    public function process(\Magento\Framework\View\Asset\PreProcessor\Chain $chain)
    {
        if (basename($chain->getAsset()->getPath()) !== self::FILENAME) {
            return false;
        }

        $this->ruleProvider->collectRules($chain);

        $variableInitialisations = array_map(
            function ($variable) {
                return $variable . ': false;';
            },
            $this->getAll($chain)
        );

        $chain->setContent(
            implode("\n", $variableInitialisations) . "\n\n" . $chain->getContent()
        );
    }

    /**
     *
     */
    private function getAll(\Magento\Framework\View\Asset\PreProcessor\Chain $chain): array
    {
        $asset = $chain->getAsset();
        $key = $asset->getPath();

        if (!isset($this->variables[$key])) {
           $this->variables[$key] = [];

            $data = trim(implode("\n", $this->themeFileCollector->getAll($chain->getAsset())));

            // Clean comments
            $data = "\n" . str_replace("\r", '', $data);
            $data = preg_replace("/\n\/\/[^\n]*\n/sU", "\n", $data);
            $data = preg_replace("/\n\/\/[^\n]*\n/sU", "\n", $data);
            $data = preg_replace("/\n\/\/[^\n]*\n/sU", "\n", $data);

            // Start with the variables that have been injected via the
            // inject_rules command
            $vars = $this->ruleProvider->getInjectedVariables($chain);

            if (preg_match_all('/@[a-zA-Z0-9_\-]+/', $data, $directMatches)) {
                $vars = array_merge($vars, array_unique($directMatches[0]));
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

       return $this->variables[$key];
    }
}
