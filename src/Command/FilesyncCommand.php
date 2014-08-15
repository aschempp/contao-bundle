<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package Contao\ContaoBundle
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

namespace Contao\ContaoBundle\Command;

use Contao\Dbafs;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Triggers the Contao file synchronization
 *
 * @author Leo Feyer <https://contao.org>
 */
class FilesyncCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('contao:filesync')
            ->setDescription('Triggers the Contao file synchronization')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file = fopen(__FILE__, 'r');

        // Lock the file
        if (!flock($file, LOCK_EX|LOCK_NB)) {
            $output->writeln('<error>The script is running already.</error>');
            fclose($file);

            return 1;
        }

        $strLog = Dbafs::syncFiles();

        $output->writeln("Synchronization complete (see $strLog).");

        // Release the lock
        flock($file, LOCK_UN);
        fclose($file);

        return 0;
    }
}
