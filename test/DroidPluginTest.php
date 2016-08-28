<?php

namespace Droid\Test\Plugin\Fs;

use PHPUnit_Framework_TestCase;

use Droid\Plugin\Fs\DroidPlugin;

class DroidPluginTest extends PHPUnit_Framework_TestCase
{
    protected $plugin;

    protected function setUp()
    {
        $this->plugin = new DroidPlugin('droid');
    }

    public function testGetCommandsReturnsAllCommands()
    {
        $this->assertSame(
            array(
                'Droid\Plugin\Fs\Command\FsChmodCommand',
                'Droid\Plugin\Fs\Command\FsChownCommand',
                'Droid\Plugin\Fs\Command\FsCopyCommand',
                'Droid\Plugin\Fs\Command\FsMkdirCommand',
                'Droid\Plugin\Fs\Command\FsMountCommand',
                'Droid\Plugin\Fs\Command\FsRenameCommand',
                'Droid\Plugin\Fs\Command\FsTemplateCommand',
                'Droid\Plugin\Fs\Command\FsTouchCommand',
            ),
            array_map(
                function ($x) {
                    return get_class($x);
                },
                $this->plugin->getCommands()
            )
        );
    }
}
