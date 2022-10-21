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


$compiler = $objectManager->get(\FishPig\LessPro\Framework\View\Design\Theme\Less\Compiler::class);
$templateEngine = $objectManager->get(\FishPig\LessPro\Framework\View\Design\Theme\Less\Template\Engine::class);
$variableProvider = $objectManager->get(\FishPig\LessPro\Framework\View\Design\Theme\Less\VariableProvider::class);
$variableProvider->testCssRuleGeneration();

if (false) {
    echo $templateEngine->render(
        file_get_contents(
            BP . '/app/code/FishPig/LayerPro/view/frontend/web/css/template.less/_module.template.less'
        )
    );

    exit;
}

if (false) {

    echo $templateEngine->render(
        file_get_contents(
            BP . '/app/design/frontend/FishPig/blank/web/css/source/_blocks.template.less'
        ),
        $variableProvider->getThemeVariables('frontend/FishPig/default')
    );
    exit;
}




$compiler->compileModules();
$compiler->compileTheme('frontend/FishPig/default');
