<?php

namespace App\Services\DownloadExternalEvents;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use App\Entity\Events;
use Doctrine\ORM\EntityManagerInterface;

class CinemaRepertuarDownloader implements DownloadExternalEventsInterface
{
    private Client $client;
    private EntityManagerInterface $entityManager;
    private string $baseUrl = 'https://www.novekino.pl/kina/przedwiosnie';
    private \DateTime $currentDateTime;

    public function __construct(Client $client, EntityManagerInterface $entityManager)
    {
        $this->client = $client;
        $this->entityManager = $entityManager;
        // Set the current date and time.
        $this->currentDateTime = new \DateTime();
    }

    /**
     * Downloads the cinema repertuar page for the next 5 days,
     * parsing the table and storing events.
     */
    public function execute(InputInterface $input, OutputInterface $output): void
    {
        $allEventsData = [];

        // Loop for the next 5 days.
        for ($i = 0; $i < 5; $i++) {
            // Calculate the date for this iteration, based on the current date.
            $date = clone $this->currentDateTime;
            $date->modify("+{$i} day");
            $dateParam = $date->format('Y-m-d');

            // Build the URL with the date parameter.
            $url = sprintf('%s/repertuar.php?data=%s#1', $this->baseUrl, $dateParam);
            $output->writeln("Fetching cinema repertuar for {$dateParam} from: $url");

            try {
                $response = $this->client->request('GET', $url);
                $html = $response->getBody()->getContents();

                $crawler = new Crawler($html);

                // Process each movie row in the table.
                $crawler->filter('table.repertoire-list tr.repertoire-movie-tr')->each(function (Crawler $tr) use (&$allEventsData, $output) {
                    // Get movie details from the first cell.
                    $infoTd = $tr->filter('td.repertoire-movie-info-td');

                    // Get the link and image from the poster.
                    $posterAnchor = $infoTd->filter('div.repertoire-movie-poster a');
                    $movieLink = $posterAnchor->attr('href');
                    if ($movieLink && strpos($movieLink, 'http') !== 0) {
                        // Ensure that relative links are built properly.
                        $movieLink = rtrim($this->baseUrl, '/') . '/' . ltrim($movieLink, '/');
                    }
                    $img = $posterAnchor->filter('img')->attr('src');

                    // Get the movie title.
                    $title = trim($infoTd->filter('div.repertoire-movie-title a')->text());

                    // Build the description (production and genre).
                    $descriptionHtml = $infoTd->filter('div.repertoire-movie-description')->html();
                    $description = str_replace(['<br>', "\n"], ' | ', strip_tags($descriptionHtml));
                    $description = trim($description, " |");

                    $timesTd = $tr->filter('td.repertoire-movie-times-td');
                    $timesTd->filter('a.repertoire-movie-time')->each(function (Crawler $timeLink) use (&$allEventsData, $title, $movieLink, $img, $description, $output) {
                        // Skip disabled times
                        if ($timeLink->attr('class') && strpos($timeLink->attr('class'), 'repertoire-movie-time--disable') !== false) {
                            return;
                        }

                        $time = trim($timeLink->attr('data-hour'));
                        $day = trim($timeLink->attr('data-day'));

                        // Validate the extracted date and time
                        if (!$time || !$day) {
                            $output->writeln("<error>Skipping invalid time or date: {$time}, {$day}</error>");
                            return;
                        }

                        // Create a DateTime object
                        $dateTime = \DateTime::createFromFormat('Y-m-d H:i', "{$day} {$time}");

                        if (!$dateTime) {
                            $output->writeln("<error>Failed to parse date: {$day} {$time}</error>");
                            return;
                        }

                        $allEventsData[] = [
                            'date' => $dateTime->format('Y-m-d H:i'),
                            'year' => $dateTime->format('Y'),
                            'month' => $dateTime->format('m'),
                            'day' => $dateTime->format('d'),
                            'time' => $time,
                            'title' => $title,
                            'link' => $movieLink,
                            'image' => $img,
                            'description' => $description,
                        ];
                    });

                });

            } catch (\Exception $e) {
                $output->writeln("<error>Error fetching {$dateParam}: {$e->getMessage()}</error>");
            }

            // Wait 5 seconds before processing the next day.
            sleep(5);
        }

        // Write the aggregated events data to a JSON file.
        $json = json_encode($allEventsData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $publicDir = __DIR__ . '/../../public';
        if (!is_dir($publicDir)) {
            mkdir($publicDir, 0777, true);
        }
        file_put_contents($publicDir . '/cinema_repertuar.json', $json);

        // Persist events into the database.
        foreach ($allEventsData as $eventData) {
            $eventEntity = $this->createOrUpdateEventEntity($eventData);
            $this->entityManager->persist($eventEntity);
        }
        $this->entityManager->flush();
    }

    /**
     * Creates or updates an event entity based on the scraped data.
     */
    private function createOrUpdateEventEntity(array $eventData): Events
    {
        $source = 'novekino';
        $eventDateTime = $eventData['date'];
        $externalId = md5($eventData['title'] . $eventDateTime);

        // Try to find an existing event by external_id and source.
        $event = $this->entityManager
            ->getRepository(Events::class)
            ->findOneBy(['external_id' => $externalId, 'source' => $source]);

        if (!$event) {
            $event = new Events($externalId, $source);
        }

        // Always update the event properties.
        $event->setTitle($eventData['title']);
        $event->setDate(new \DateTime($eventData['date']));
        $event->setDescription("Nove kino Przedwiośnie zaprasza na film. \n" . $eventData['description']);
        $event->setImage($eventData['image']);
        $event->setLink($eventData['link']);
        $event->setTypeEvent("cinema");
        $event->setCategory(['kino']);

        return $event;
    }
}
