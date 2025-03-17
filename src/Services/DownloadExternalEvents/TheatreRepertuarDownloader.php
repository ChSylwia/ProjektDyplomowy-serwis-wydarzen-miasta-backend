<?php

namespace App\Services\DownloadExternalEvents;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use App\Entity\Events;
use Doctrine\ORM\EntityManagerInterface;

class TheatreRepertuarDownloader implements DownloadExternalEventsInterface
{
    private Client $client;
    private EntityManagerInterface $entityManager;
    // Base URL is used to build full URLs when relative links are found.
    private string $baseUrl = 'https://www.teatrplock.pl';

    public function __construct(Client $client, EntityManagerInterface $entityManager)
    {
        $this->client = $client;
        $this->entityManager = $entityManager;
    }

    /**
     * Downloads the repertuar page, parses the table and stores events.
     */
    public function execute(InputInterface $input, OutputInterface $output): void
    {
        $month = date('m');
        $year = date('Y');
        $url = sprintf('https://www.teatrplock.pl/pl/repertuar-new/%s/%s', $month, $year);

        $output->writeln("Fetching theatre repertuar from: $url");

        $response = $this->client->request('GET', $url);
        $html = $response->getBody()->getContents();

        $crawler = new Crawler($html);

        $captionText = $crawler->filter('table#repertuar caption')->text();
        if (preg_match('/\((\d{2})\/(\d{4})\)/', $captionText, $matches)) {
            $month = $matches[1];
            $year = $matches[2];
        } else {
            $month = date('m');
            $year = date('Y');
        }
        $output->writeln("Extracted month/year: $month/$year");

        $currentDayText = null;
        $eventsData = [];

        $crawler->filter('table#repertuar tbody tr')->each(function (Crawler $tr, $i) use (&$currentDayText, $year, $month, &$eventsData) {
            $tds = $tr->filter('td');
            $firstTd = $tds->eq(0);
            if ($firstTd->filter('span')->count() > 0) {
                $currentDayText = trim($firstTd->filter('span')->text());
            }
            if ($firstTd->filter('span')->count() > 0) {
                $timeTd = $tds->eq(1);
                $titleTd = $tds->eq(2);
                $sceneTd = $tds->eq(3);
            } else {
                $timeTd = $tds->eq(0);
                $titleTd = $tds->eq(1);
                $sceneTd = $tds->eq(2);
            }

            $timeText = trim(str_replace('godz.', '', $timeTd->text()));
            $titleLink = $titleTd->filter('a');
            $title = $titleLink->count() ? trim($titleLink->text()) : trim($titleTd->text());
            $href = $titleLink->count() ? $titleLink->attr('href') : '';
            if ($href && strpos($href, 'http') !== 0) {
                $href = $this->baseUrl . $href;
            }
            $scene = trim($sceneTd->text());

            if (preg_match('/(\d{1,2})/', $currentDayText, $dayMatches)) {
                $day = str_pad($dayMatches[1], 2, '0', STR_PAD_LEFT);
            } else {
                $day = '01';
            }
            $dateString = sprintf('%s-%s-%s %s', $year, $month, $day, $timeText);
            $date = \DateTime::createFromFormat('Y-m-d H:i', $dateString);
            if (!$date) {
                $date = \DateTime::createFromFormat('Y-m-d', sprintf('%s-%s-%s', $year, $month, $day));
            }

            $description = "Teatr dramatyczny w Płocku zaprasza na sztukę. \n Scena: " . $scene;
            $image = 'https://chwile-plocka.s3.amazonaws.com/Teatr-dramatyczny-plock.png';
            $eventsData[] = [
                'date' => $date->format('Y-m-d H:i'),
                'title' => $title,
                'link' => $href,
                'image' => $image,
                'description' => $description,
                'time' => $timeText,
                'scene' => $scene,
            ];
        });

        $json = json_encode($eventsData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        // Ensure the public directory exists
        $publicDir = __DIR__ . '/../../public';
        if (!is_dir($publicDir)) {
            mkdir($publicDir, 0777, true);
        }

        foreach ($eventsData as $eventData) {
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
        // We generate an external ID based on a hash of the title and date.
        $externalId = md5($eventData['title'] . $eventData['date']);
        $source = 'teatrplock';

        // Check if an event with the same external ID and source already exists.
        $event = $this->entityManager
            ->getRepository(Events::class)
            ->findOneBy(['external_id' => $externalId, 'source' => $source]);

        if (!$event) {
            $event = new Events($externalId, $source);
        }

        // Update the event properties.
        $event->setTitle($eventData['title']);
        $event->setDate(new \DateTime($eventData['date']));
        $event->setDescription($eventData['description']);
        $event->setImage($eventData['image']);
        $event->setLink($eventData['link']);
        $event->setTypeEvent("local-event");
        $event->setCategory(['teatr']);


        return $event;
    }
}
