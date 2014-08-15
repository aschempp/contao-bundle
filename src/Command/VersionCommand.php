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

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Displays the Contao version number
 *
 * @author Leo Feyer <https://contao.org>
 */
class VersionCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('contao:version')
            ->setDescription('Displays the Contao version number')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(VERSION . '.' . BUILD);
    }
}
