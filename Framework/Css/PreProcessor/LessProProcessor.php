<?php
/**
 *
 */
declare(strict_types=1);

namespace FishPig\LessPro\Framework\Css\PreProcessor;

class LessProProcessor implements \Magento\Framework\View\Asset\PreProcessorInterface
{
    /**
     *
     */
    public function __construct(
        \FishPig\LessPro\Framework\View\Design\Theme\Less\TemplateRenderer $lessTemplateRenderer
    ) {
        $this->lessTemplateRenderer = $lessTemplateRenderer;
    }

    /**
     * @inheritdoc
     */
    public function process(\Magento\Framework\View\Asset\PreProcessor\Chain $chain)
    {
        if ($chain->getContentType() !== 'less') {
            return;
        }

        $context = $chain->getAsset()->getContext();

        $renderedTemplate = $this->lessTemplateRenderer->render(
            $chain->getContent(),
            $context->getAreaCode() . '/' . $context->getThemePath()
        );

        if ($renderedTemplate !== null) {
            $chain->setContent($renderedTemplate);
        }
    }
}
