<?php
namespace Droid\Plugin\Fs\Command;

use RuntimeException;

use Droid\Lib\Plugin\Command\CheckableTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Droid\Lib\Plugin\Model\File\FileFactory;
use Droid\Lib\Plugin\Model\File\NameValueLine;
use Droid\Lib\Plugin\Model\File\UnusableFileException;

class FsSetlineCommand extends Command
{
    use CheckableTrait;

    protected $fileFactory;

    public function __construct(
        FileFactory $fileFactory,
        $name = null
    ) {
        $this->fileFactory = $fileFactory;
        return parent::__construct($name);
    }

    public function configure()
    {
        $this->setName('fs:setline')
            ->setDescription('Add a (name, value) pair to a line based file, or update an existing (name, value) pair.')
            ->addArgument(
                'file',
                InputArgument::REQUIRED,
                'The path to a line based file'
            )
            ->addArgument(
                'option-name',
                InputArgument::REQUIRED,
                'The name in a (name, value) pair'
            )
            ->addArgument(
                'option-value',
                InputArgument::REQUIRED,
                'The value in a (name, value) pair'
            )
            ->addOption(
                '--separator',
                's',
                InputOption::VALUE_REQUIRED,
                'Characters which separate the name from the value in a (name, value) pair.'
            )
            ->addOption(
                '--compare-values',
                'm',
                InputOption::VALUE_NONE,
                'Prevent duplicate lines as normal, but compare the contents of the "value" field instead of the "name" field.'
            )
        ;
        $this->configureCheckMode();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->activateCheckMode($input);

        if (! file_exists($input->getArgument('file'))) {
            throw new RuntimeException(
                sprintf('The file "%s" does not exist.', $input->getArgument('file'))
            );
        }

        if ($input->getOption('separator')) {
            $this
                ->fileFactory
                ->getLineFactory()
                ->setFieldSeparator($input->getOption('separator'))
            ;
        }
        if ($input->getOption('compare-values')) {
            $this
                ->fileFactory
                ->getLineFactory()
                ->setMappingFields(array(NameValueLine::FIELD_VALUE))
            ;
        }

        $file = $this->fileFactory->makeFile($input->getArgument('file'));
        $line = $this->fileFactory->getLineFactory()->makeLine();

        $line
            ->setFieldValue(
                NameValueLine::FIELD_NAME,
                $input->getArgument('option-name')
            )
            ->setFieldValue(
                NameValueLine::FIELD_VALUE,
                $input->getArgument('option-value')
            )
        ;

        try {
            $file->setLine($line);
        } catch (UnusableFileException $e) {
            throw new RuntimeException(
                'I cannot set a line in the file',
                null,
                $e
            );
        }

        if (! $file->changed()) {
            $output->WriteLn(
                sprintf(
                    'I am not making any changes to the file "%s"; it already has the line "%s%s%s".',
                    $input->getArgument('file'),
                    $input->getArgument('option-name'),
                    $this->fileFactory->getLineFactory()->getFieldSeparator(),
                    $input->getArgument('option-value')
                )
            );
            $this->reportChange($output);
            return 0;
        } elseif ($this->checkMode()) {
            $this->markChange();
            $output->WriteLn(
                sprintf(
                    'I would make a change to the file "%s".',
                    $input->getArgument('file')
                )
            );
            $this->reportChange($output);
            return 0;
        }

        $this->markChange();
        $output->WriteLn(
            sprintf(
                'I am making your changes to the file "%s".',
                $input->getArgument('file')
            )
        );
        $file
            ->backup($this->backupName($input->getArgument('file')))
            ->finish()
        ;
        $this->reportChange($output);
    }

    private function backupName($originalName)
    {
        return sprintf('%s.%s.backup', $originalName, date('Y-m-d_H-i-s'));
    }
}
