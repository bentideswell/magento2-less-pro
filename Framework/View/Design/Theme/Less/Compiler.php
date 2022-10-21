<?php
/**
 *
 */
namespace FishPig\LessPro\Framework\View\Design\Theme\Less;

class Compiler
{
    /**
     *
     */
    const TEMPLATE_FILE_EXTENSION = '.template.less';
    const TEMPLATE_DIR  = 'template.less';

    /**
     *
     */
    const BRAND = '// Precompiled by FishPig_LessTemplates';

    /**
     *
     */
    public function __construct(
        \FishPig\LessPro\Framework\View\Design\Theme\Less\FileCollector $fileCollector,
        \FishPig\LessPro\Framework\View\Design\Theme\Less\VariableProvider $lessVariableProvider,
        \FishPig\LessPro\Framework\View\Design\Theme\Less\MediaQueryPrefixProvider $mediaQueryPrefixProvider,
        \FishPig\LessPro\Framework\View\Design\Theme\Less\Template\Engine $templateEngine
    ) {
        $this->fileCollector = $fileCollector;
        $this->lessVariableProvider = $lessVariableProvider;
        $this->mediaQueryPrefixProvider = $mediaQueryPrefixProvider;
        $this->templateEngine = $templateEngine;
    }

    /**
     *
     */
    public function compileModules(): void
    {
        $templateFiles = $this->fileCollector->getFiles(
            'app/code/FishPig',
            self::TEMPLATE_FILE_EXTENSION
        );

        foreach ($templateFiles as $templateFile) {
            $this->createFile(
                $templateFile,
                $this->templateEngine->renderFile($templateFile)
            );
        }
    }

    /**
     *
     */
    public function compileTheme($theme): void
    {
        $templateFiles = $this->fileCollector->getFilesByTheme(
            $theme,
            self::TEMPLATE_FILE_EXTENSION
        );

        // Theme variables
        $variables = $this->lessVariableProvider->getThemeVariables($theme);

        foreach ($templateFiles as $templateFile) {
            $this->createFile(
                $templateFile,
                $this->templateEngine->renderFile(
                    $templateFile,
                    $variables
                )
            );
        }
    }


    private function createFile(\Magento\Framework\View\File $template, string $content): void
    {
        $targetFile = str_replace(
            '/' . self::TEMPLATE_DIR . '/',
            '/source/',
            str_replace(
                self::TEMPLATE_FILE_EXTENSION,
                    '.less',
                    $template->getFilename()
            )
        );

        $targetDir = dirname($targetFile);

        // Check whether file exists and can be replaced
        if (is_file($targetFile)) {
            $targetContents = file_get_contents($targetFile);

            if ($targetContents !== '' && strpos($targetContents, self::BRAND) === false) {
                throw new \RuntimeException(
                    sprintf(
                        'Precompilation template target file exists (%s) and does not appear to be created by us.',
                        $targetFile
                    )
                );
            }
        } elseif (!is_dir($targetDir)) {
            @mkdir($targetDir);

            if (!is_dir($targetDir)) {
                throw new \RuntimeException(
                    sprintf(
                        'Cannot create target directory (%s) for CSS compilation.',
                        $targetDir
                    )
                );
            }
        }

        // Add the brand mark. This allows us to edit the file in future and
        // stops accidentally deleting files that have not been precompiled by us
        $content = self::BRAND . "\n" . $content;

        // Create the target file
        @file_put_contents($targetFile, $content);

        // Now check that result file was created
        if (!is_file($targetFile)) {
            throw new \RuntimeException(
                sprintf(
                    'An error occured creating the target LESS file @ "%s"',
                    $targetFile
                )
            );
        }
    }
}
