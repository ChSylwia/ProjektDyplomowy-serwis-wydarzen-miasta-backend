<?php

namespace App\Services\DownloadExternalEvents;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface DownloadExternalEventsInterface
{
    public function execute(InputInterface $input, OutputInterface $output): void;
}

