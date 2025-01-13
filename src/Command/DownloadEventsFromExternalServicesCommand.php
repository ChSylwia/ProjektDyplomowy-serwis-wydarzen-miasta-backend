<?php

namespace App\Command;

use App\Services\DownloadExternalEvents\DownloadExternalEventsInterface;
use App\Services\DownloadExternalEvents\EbiletEventsDownloader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Psr\Log\LoggerInterface;

#[AsCommand(
    name: 'app:download-events',
    description: 'This command downloads events from multiple external services.',
)]
class DownloadEventsFromExternalServicesCommand extends Command
{
    private iterable $downloaders;
    private LoggerInterface $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(
        EbiletEventsDownloader $ebiletEventsDownloader,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->downloaders = [$ebiletEventsDownloader];
        $this->logger = $logger;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Starting the download process for events...');

        foreach ($this->downloaders as $downloader) {
            try {
                $output->writeln('Running downloader: ' . get_class($downloader));
                $downloader->execute($input, $output);
                $output->writeln('Downloader executed successfully: ' . get_class($downloader));
            } catch (\Exception $e) {
                $errorMessage = 'Error in ' . get_class($downloader) . ': ' . $e->getMessage();
                $this->logger->error($errorMessage);
                $output->writeln('<error>' . $errorMessage . '</error>');
            }
        }

        $output->writeln('Download process completed.');
        return Command::SUCCESS;
    }
}
