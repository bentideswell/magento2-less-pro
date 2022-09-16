<?php
/**
 *
 */
declare(strict_types=1);

namespace FishPig\LessPro\Framework\Css\PreProcessor;

use Magento\Framework\View\Asset\PreProcessor\Chain;

class UndefinedVariableInjector implements \Magento\Framework\View\Asset\PreProcessorInterface
{
    /**
     *
     */
    public function __construct(
        \FishPig\LessPro\Framework\View\Design\Theme\VariableProvider $themeVariableProvider
    ) {
        $this->themeVariableProvider = $themeVariableProvider;
    }

    /**
     * @inheritdoc
     */
    public function process(Chain $chain)
    {

        if (!$this->isTargetAsset($chain->getAsset())) {
            return;
        }


        $variableInitialisations = array_map(
            function ($variable) {
                return $variable . ': false;';
            },
            $this->themeVariableProvider->getAll($chain->getAsset())
        );

        $chain->setContent(
            implode("\n", $variableInitialisations) . "\n\n" . $chain->getContent()
        );
    }

    /**
     *
     */
    private function isTargetAsset(\Magento\Framework\View\Asset\File $asset): bool
    {
        if (!in_array(basename($asset->getPath()), ['_variables-init.less'])) {
            return false;
        }

        return true;
    }
}
