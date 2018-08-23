<?php
namespace Skill\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2017 Michael Mifsud
 * @todo: maybe
 */
class Cache extends \Tk\Console\Console
{

    /**
     *
     */
    protected function configure()
    {
        $this->setName('skill-cache')
            ->setDescription('Re-calculate the skill grades and averages and cache the results onto the filesystem');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        // required vars
        $config = \App\Config::getInstance();

        if (\App\Config::getInstance()->getEventDispatcher()) {
            $e = new \Tk\Event\Event();
            $e->set('console', $this);
            \App\Config::getInstance()->getEventDispatcher()->dispatch(\App\AppEvents::CONSOLE_CRON, $e);
        }

    }

}
