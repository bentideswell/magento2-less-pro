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
    private $fileCollector = null;

    /**
     *
     */
    private $lessVariableProvider = null;

    /**
     *
     */
    private $mediaQueryPrefixProvider = null;

    /**
     *
     */
    private $templateEngine = null;

    /**
     *
     */
    private static $filenameConversionMap = [
        '/source/' => '/' . self::TEMPLATE_DIR . '/',
        '.less' => self::TEMPLATE_FILE_EXTENSION
    ];

    /**
     *
     */
    const TEMPLATE_FILE_EXTENSION = '.template.less';
    const TEMPLATE_DIR  = 'template.less';

    /**
     *
     */
    const BRAND = '// Precompiled by FishPig_LessPro';

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

    /**
     *
     */
    private function createFile(\Magento\Framework\View\File $template, string $content): void
    {
        $targetFile = $this->getTargetFilename($template->getFilename());

        $targetDir = dirname($targetFile);

        // Check whether file exists and can be replaced
        if (is_file($targetFile)) {
            $targetContents = file_get_contents($targetFile);

            if ($targetContents !== '' && strpos($targetContents, self::BRAND) === false) {
                return;
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

    /**
     *
     */
    public static function getTargetFilename(string $filename): string
    {
        if (!self::stringEndsWith($filename, self::TEMPLATE_FILE_EXTENSION)) {
            throw new \RuntimeException(
                sprintf(
                    'Unable to convert "%s" to a target filename as it already is one.',
                    $filename
                )
            );
        }

        $filename = str_replace('/' . self::TEMPLATE_DIR . '/', '/source/', $filename);
        $filename = preg_replace('/' . preg_quote(self::TEMPLATE_FILE_EXTENSION, '/') . '$/', '.less', $filename);

        return $filename;
    }

    /**
     *
     */
    public static function getTemplateFilename(string $filename): string
    {
        if (self::stringEndsWith($filename, self::TEMPLATE_FILE_EXTENSION)) {
            throw new \RuntimeException(
                sprintf(
                    'Unable to convert "%s" to it\'s original filename as it already appears to be the original',
                    $filename
                )
            );
        }

        $filename = str_replace('/source/', '/' . self::TEMPLATE_DIR . '/', $filename);
        $filename = preg_replace('/\.less$/', self::TEMPLATE_FILE_EXTENSION, $filename);
        return $filename;
    }

    /**
     *
     */
    private static function stringEndsWith(string $str, string $endsWith): bool
    {
        return strlen($str) >= strlen($endsWith) && substr($str, -strlen($endsWith)) === $endsWith;
    }
}
