<?php
/**
 *
 */
declare(strict_types=1);

namespace FishPig\LessPro\Plugin\Magento\Framework\View\Asset;

use Magento\Framework\View\Asset\Repository;

class RepositoryPlugin
{
    /**
     *
     */
    private $hasAlreadyRan = false;

    /**
     *
     */
    public function __construct(
        \FishPig\LessPro\Framework\View\Design\Theme\Less\Compiler $compiler
    ) {
        $this->compiler = $compiler;
    }

    /**
     *
     */
    public function beforeCreateAsset(Repository $subject, $file, $params = [])
    {
        if ($this->hasAlreadyRan !== true) {
            if ($this->isTriggerFile($file, $params)) {
                $this->hasAlreadyRan = true;
                $this->compiler->compileModules();
                $this->compiler->compileTheme(
                    $params['area'] . '/' . $params['theme']
                );
            }
        }
        return [$file, $params];
    }

    /**
     *
     */
    private function isTriggerFile($file, $params): bool
    {
        return substr($file, -4) === '.css' && isset($params['area']) && $params['area'] === 'frontend';
    }
}
