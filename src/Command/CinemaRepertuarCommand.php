<?php

namespace App\Command;

use App\Services\DownloadExternalEvents\CinemaRepertuarDownloader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Psr\Log\LoggerInterface;

#[AsCommand(
    name: 'app:download-cinema-repertuar',
    description: 'Downloads cinema repertuar events for the next 7 days and saves them to JSON and the database'
)]
class CinemaRepertuarCommand extends Command
{
    /**
     * @var iterable<CinemaRepertuarDownloader>
     */
    private iterable $downloaders;

    private LoggerInterface $logger;

    public function __construct(
        CinemaRepertuarDownloader $cinemaRepertuarDownloader,
        LoggerInterface $logger
    ) {
        parent::__construct();
        // Wrap the downloader in an array for consistency with multiple services.
        $this->downloaders = [$cinemaRepertuarDownloader];
        $this->logger = $logger;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Starting cinema repertuar download process...');

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

        $output->writeln('Cinema repertuar download process completed.');
        return Command::SUCCESS;
    }
}
