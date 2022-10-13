<?php
/**
 * @author Ben Tideswell (ben@fishpig.com)
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include __DIR__ . '/../../../../app/bootstrap.php';
$objectManager = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER)->getObjectManager();
$objectManager->get(\Magento\Framework\App\State::class)->setAreaCode(\Magento\Framework\App\Area::AREA_FRONTEND);

$variableProvider = $objectManager->get(\FishPig\LessPro\Framework\View\Design\Theme\Less\VariableProvider::class);
$templateRenderer = $objectManager->get(\FishPig\LessPro\Framework\View\Design\Theme\Less\TemplateRenderer::class);

$variableProvider->testCssRuleGeneration();




echo $templateRenderer->render(
    file_get_contents(
        BP . '/app/design/frontend/FishPig/blank/web/css/source/_frame.less'
    ),
    'frontend/FishPig/default'
);



//print_r($variableNameProvider->getAll('frontend/FishPig/default'));
