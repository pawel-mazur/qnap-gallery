<?php

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

$console = new Application('My Silex Application', 'n/a');
$console->getDefinition()->addOption(new InputOption('--env', '-e', InputOption::VALUE_REQUIRED, 'The Environment name.', 'dev'));
$console->setDispatcher($app['dispatcher']);
$console
    ->register('database:dump')
    ->setDefinition(array(
        new InputArgument('file', InputArgument::OPTIONAL, 'Dump file', 'var/dump.sql'),
    ))
    ->setDescription('Restore database dump')
    ->setCode(function (InputInterface $input, OutputInterface $output) use ($app) {
        $file = $input->getArgument('file');

        if(shell_exec("which mysqldump")){
            $mysqldump = 'mysqldump';
        } else {
            $mysqldump = '/usr/local/mariadb/bin/mysqldump';
        }

        exec(sprintf('%s --host %s --port %s --user %s --password="%s" --single-transaction %s --result-file %s',
            $mysqldump,
            $app['database.host'],
            $app['database.port'],
            $app['database.user'],
            $app['database.password'],
            $app['database.dbname'],
            $file
        ));
    })
;
$console
    ->register('database:restore')
    ->setDefinition(array(
         new InputArgument('file', InputArgument::OPTIONAL, 'Dump file', 'var/dump.sql'),
    ))
    ->setDescription('Restore database dump')
    ->setCode(function (InputInterface $input, OutputInterface $output) use ($app) {
        $file = $input->getArgument('file');
        exec(sprintf('mysql --host %s --port %s --user %s --password="%s" %s < %s',
            $app['database.host'],
            $app['database.port'],
            $app['database.user'],
            $app['database.password'],
            $app['database.dbname'],
            $file
        ));
    })
;

return $console;
