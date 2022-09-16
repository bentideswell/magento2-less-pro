<?php
/**
 *
 */
declare(strict_types=1);

namespace FishPig\LessPro\Framework\Css\PreProcessor;

use Magento\Framework\View\Asset\PreProcessor\Chain;

class LessProProcessor implements \Magento\Framework\View\Asset\PreProcessorInterface
{
    /**
     *
     */
    public function __construct(
        \FishPig\LessPro\Framework\Css\PreProcessor\LessPro\VariableNameProvider $variableNameProvider,
        \FishPig\LessPro\Framework\Css\PreProcessor\LessPro\RuleProvider $ruleProvider
    ) {
        $this->variableNameProvider = $variableNameProvider;
        $this->ruleProvider = $ruleProvider;
    }

    /**
     * @inheritdoc
     */
    public function process(\Magento\Framework\View\Asset\PreProcessor\Chain $chain)
    {
        if ($chain->getContentType() !== 'less') {
            return;
        }

        $this->variableNameProvider->process($chain);
        $this->ruleProvider->process($chain);
    }
}
