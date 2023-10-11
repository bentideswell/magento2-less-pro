<?php
/**
 * @url FishPig.com
 */
declare(strict_types=1);

namespace FishPig\LessPro\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class CompileFileCommand extends \Symfony\Component\Console\Command\Command
{
    /**
     *
     */
    const FILE = 'file';

    /**
     *
     */
    const THEME = 'theme';

    /**
     *
     */
    const AREA = 'area';

    /**
     *
     */
    const OUTPUT = 'with-output';

    /**
     *
     */
    private $appState = null;

    /**
     *
     */
    private $assetRepository = null;

    /**
     *
     */
    public function __construct(
        \Magento\Framework\App\State $appState,
        \Magento\Framework\View\Asset\Repository $assetRepository,
        string $name = null
    ) {
        $this->appState = $appState;
        $this->assetRepository = $assetRepository;
        parent::__construct($name);
    }

    /**
     * @return $this
     */
    protected function configure()
    {
        $this->setName('fishpig:lesspro:compile');
        $this->setDescription('Manually compile a file using FishPig_LessPro');
        $this->setDefinition([
            new InputOption(self::FILE, null, InputOption::VALUE_REQUIRED),
            new InputOption(self::THEME, null, InputOption::VALUE_REQUIRED),
            new InputOption(self::AREA, null, InputOption::VALUE_REQUIRED),
            new InputOption(self::OUTPUT, null, InputOption::VALUE_NONE)
        ]);
        return parent::configure();
    }

    /**
     * @param  InputInterface $input
     * @param  OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->appState->setAreaCode('frontend');

        if (!($file = $input->getOption(self::FILE))) {
            throw new \InvalidArgumentException(
                sprintf(
                    'You did not specify a filename (eg. --%s=css/styles.css)',
                    self::FILE
                )
            );
        } elseif (!($theme = $input->getOption(self::THEME))) {
            throw new \InvalidArgumentException(
                sprintf(
                    'You did not specify a filename (eg. --%s=Magento/luma)',
                    self::THEME
                )
            );
        }

        $asset = $this->assetRepository->createAsset(
            $file,
            [
                'area' => $input->getOption(self::AREA) ?: 'frontend',
                'theme' => $theme
            ]
        );

        if (!($content = $asset->getContent())) {
            throw new \RuntimeException(
                'Asset creation was successful but content is empty.'
            );
        }

        if ($input->getOption(self::OUTPUT)) {
            $output->writeLn($asset->getContent());
        } else {
            $output->writeLn('OK');
        }

        return parent::SUCCESS;
    }
}
