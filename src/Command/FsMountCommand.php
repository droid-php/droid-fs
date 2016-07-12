<?php
namespace Droid\Plugin\Fs\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Droid\Lib\Plugin\Command\CheckableTrait;
use Droid\Plugin\Fs\FstabLine;
use RuntimeException;

class FsMountCommand extends Command
{
    use CheckableTrait;

    public function configure()
    {
        $this->setName('fs:mount')
            ->setDescription('Mount a filesystem by updating /etc/fstab')
            ->addArgument(
                'filesystem',
                InputArgument::REQUIRED,
                'Source filename'
            )
            ->addArgument(
                'mount-point',
                InputArgument::REQUIRED,
                'Mount-pount, for example /mnt/my-fs'
            )
            ->addArgument(
                'type',
                InputArgument::REQUIRED,
                'Filesystem type, for example: ext3, proc, swap, nfs'
            )
            ->addOption(
                'fstab',
                null,
                InputOption::VALUE_REQUIRED,
                'Name of the fstab filename, defaults to /etc/fstab',
                '/etc/fstab',
                'defaults'
            )
            ->addOption(
                'options',
                'o',
                InputOption::VALUE_REQUIRED,
                'Options'
            )
            ->addOption(
                'dump',
                'd',
                InputOption::VALUE_REQUIRED,
                0
            )
            ->addOption(
                'pass',
                'p',
                InputOption::VALUE_REQUIRED,
                0
            )
        ;
        $this->configureCheckMode();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->activateCheckMode($input);
        $fileSystem = $input->getArgument('filesystem');
        $mountPoint = $input->getArgument('mount-point');
        $type = $input->getArgument('type');
        $options = $input->getOption('options');
        if (!$options) {
            $options = 'defaults';
        }
        $dump = $input->getOption('dump');
        if (!$dump) {
            $dump = '0';
        }
        $pass = $input->getOption('pass');
        if (!$pass) {
            $pass = '0';
        }
        $fstab = $input->getOption('fstab');

        $output->WriteLn("Updating $fstab");
        if (!file_exists($fstab)) {
            throw new RuntimeException('fstab file does not exist: ' . $fstab);
        }

        $this->markChange();

        if ($this->checkMode()) {
            $this->reportChange($output);
            return 0;
        }

        $content = file_get_contents($fstab);
        $rows = explode("\n", $content);
        foreach ($rows as $row) {
            $line = new FstabLine();
            $line->setContent($row);
            $lines[] = $line;
        }
        //print_r($lines);

        $newLine = null;
        foreach ($lines as $line) {
            if (trim($line->getFileSystem())==$fileSystem) {
                $newLine = $line;
            }
        }
        if (!$newLine) {
            $newLine = new FstabLine();
            $newLine->setType('mount');
            $lines[] = $newLine;
        }
        $newLine->setFileSystem($fileSystem);
        $newLine->setMountPoint($mountPoint);
        $newLine->setFileSystemType($type);
        $newLine->setOptions($options);
        $newLine->setDump($dump);
        $newLine->setPass($pass);

        $o = '';
        foreach ($lines as $line) {
            $o .= $line->render();
        }
        //echo $o;
        if ($o != $content) {
            file_put_contents($fstab . '.'. date('Y-m-d_H-i-s') . '.backup', $content);
        }
        file_put_contents($fstab, $o);

        $cmd = 'mountpoint ' . $newLine->getMountPoint();
        $process = new Process($cmd);
        $process->run();
        if ($process->isSuccessful()) {
            $output->WriteLn("Already mounted " . $newLine->getMountPoint());
            return 0;
        }

        $cmd = 'mount ' . $newLine->getMountPoint();
        $process = new Process($cmd);
        $output->writeLn($process->getCommandLine());
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $this->reportChange($output);
    }
}
