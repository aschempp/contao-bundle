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

use Contao\Automator;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * Triggers a Contao automator task
 *
 * @author Leo Feyer <https://contao.org>
 */
class AutomatorCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('contao:automator')
            ->setDefinition([
                new InputArgument('task', InputArgument::OPTIONAL, 'The task to execute'),
            ])
            ->setDescription('Triggers a Contao automator task via the command line')
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

        $commands = [];

        // Find all public methods
        $class   = new \ReflectionClass('Automator');
        $methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if ('Contao\Automator' === $method->class && '__construct' !== $method->name) {
                $commands[] = $method->name;
            }
        }

        $task = $input->getArgument('task');

        // Let the user choose if no task is given
        if (null === $task) {

            /** @var QuestionHelper $helper */
            $helper = $this->getHelper('question');

            $question = new ChoiceQuestion('Please select a task', $commands, 0);
            $task     = $helper->ask($input, $output, $question);
        }

        if (!in_array($task, $commands)) {
            $output->writeln('Invalid command "' . $task . '".');

            return 1;
        }

        $automator = new Automator;
        $automator->$task();

        $output->writeln('<info>The "' . $task . '" task has been completed.</info>');

        // Release the lock
        flock($file, LOCK_UN);
        fclose($file);

        return 0;
    }
}
