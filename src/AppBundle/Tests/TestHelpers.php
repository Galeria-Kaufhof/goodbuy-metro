<?php

namespace AppBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

trait TestHelpers
{
    protected function resetDatabase()
    {
        $client = static::createClient();
        $kernel = $client->getKernel();
        $kernel->boot();

        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArgvInput(['', 'doctrine:database:drop', '--no-interaction', '--force', '-q']);
        $application->run($input);

        $input = new ArgvInput(['', 'doctrine:database:create', '--no-interaction', '-q']);
        $application->run($input);

        $output = new ConsoleOutput();
        $input = new ArgvInput(['', 'doctrine:migrations:migrate', '--no-interaction', '-q']);
        $application->run($input, $output);
    }
}
