<?php

namespace App\Services\DownloadExternalEvents;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use GuzzleHttp\Client;
use App\Entity\Events;
use Doctrine\ORM\EntityManagerInterface;

class EbiletEventsDownloader implements DownloadExternalEventsInterface
{
    private Client $client;
    private EntityManagerInterface $entityManager;

    public function __construct(Client $client, EntityManagerInterface $entityManager)
    {
        $this->client = $client;
        $this->entityManager = $entityManager;
    }
    public function execute(InputInterface $input, OutputInterface $output): void
    {
        $maxRetries = 7;
        $currentDate = new \DateTime();
        $currentDateString = $currentDate->format('Y-m-d');

        $endDate = (clone $currentDate)->add(new \DateInterval('P1M')); // One month ahead
        $endDateString = $endDate->format('Y-m-d');

        $offset = 0;
        for ($retry = 0; $retry < $maxRetries; $retry++) {

            $output->writeln("Fetching events for period: $currentDateString to $endDateString, and offset: $offset");

            $events = $this->downloadEvents($currentDateString, $endDateString, $offset);
            $count = count($events);
            $offset = $offset + $count;

            // Always flush events, regardless of count
            foreach ($events as $eventData) {
                $eventEntity = $this->createOrUpdateEventEntity($eventData, $output);
                $this->entityManager->persist($eventEntity);
            }

            $this->entityManager->flush();
            $output->writeln("Events successfully downloaded and saved.");
            $output->writeln("Sleeping for 5 seconds...");
            sleep(5);

            if ($events === [] || $count < 20) {
                $offset = 0;
                if ($retry < $maxRetries - 1) {
                    // Shift the start date forward by one month if no events found or less than 20 events
                    $currentDate->modify('+1 month');
                    $currentDateString = $currentDate->format('Y-m-d');
                    $endDate = (clone $currentDate)->add(new \DateInterval('P1M'));
                    $endDateString = $endDate->format('Y-m-d');
                    $output->writeln("No events found or less than 20 events, shifting the date to: $currentDateString");

                    continue; // Retry with the new date
                } else {
                    $output->writeln("No events found after maximum retries.");
                    return;
                }
            }

        }
    }

    private function downloadEvents(string $startDate, string $endDate, int $offset): array
    {
        $headers = [
            'accept' => 'application/json, text/plain, */*',
            'accept-language' => 'pl-PL,pl;q=0.9,en-US;q=0.8,en;q=0.7',
            'cache-control' => 'no-cache',
            'pragma' => 'no-cache',
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36 OPR/115.0.0.0'
        ];
        $top = $offset === 0 ? 0 : $offset + 20;
        $url = 'https://www.ebilet.pl/api/TitleListing/Search?currentTab=1&sort=1'
            . '&dateFrom=' . urlencode($startDate)
            . '&dateTo=' . urlencode($endDate)
            . '&province=mazowieckie&city=' . urlencode('Płock')
            . "&top=$top&size=20";

        $request = new \GuzzleHttp\Psr7\Request('GET', $url, $headers);
        $response = $this->client->send($request);
        $data = json_decode($response->getBody()->getContents(), true);

        return $data['titles'] ?? [];
    }

    private function createOrUpdateEventEntity(array $eventData, OutputInterface $output): Events
    {
        $source = 'ebilet';
        $externalId = md5($eventData['title'] . $eventData['id']);
        // Check if the event already exists in the database
        $event = $this->entityManager
            ->getRepository(Events::class)
            ->findOneBy(['external_id' => $externalId, 'source' => $source]);

        if (!$event) {
            $output->writeln('create ' . $externalId);
            // Create a new event if it doesn't exist
            $event = new Events($externalId, $source);
        } else {
            $output->writeln('edit ' . $externalId);
        }

        // Update the event properties
        $event->setImage("https://www.ebilet.pl/media" . $eventData['imageLandscape'] ?? null);
        $event->setTitle($eventData['title'] ?? 'Unknown Title');
        $event->setDescription($eventData['metaDescription'] ?? 'No description available');
        $event->setDate(new \DateTime($eventData['dateFrom'] ?? 'now'));
        $event->setPrice(null); // Adjust if price is provided in the API
        $event->setLink("https://www.ebilet.pl/" . $eventData['category'] . "/" . $eventData['subcategory'] . "/" . $eventData['slug'] ?? null);
        $event->setTypeEvent("big-event");
        $event->setCategory($eventData['category'] === null ? ['inne'] : (array) $eventData['category']);

        return $event;
    }
}
