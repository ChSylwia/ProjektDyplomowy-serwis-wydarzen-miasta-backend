<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RepertuarController
{
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    #[Route('/api/repertuar', name: 'api_repertuar')]
    public function fetchRepertuar(): JsonResponse
    {
        $month = date('m'); // Dynamic month
        $year = date('Y');  // Dynamic year
        $url = "https://www.teatrplock.pl/pl/repertuar-new/{$month}/{$year}";

        $response = $this->client->request('GET', $url);
        $html = $response->getContent();

        $crawler = new Crawler($html);
        $events = [];

        $crawler->filter('#repertuar tbody tr')->each(function (Crawler $row) use (&$events, $year, $month) {
            $columns = $row->filter('td');

            if ($columns->count() === 4) {
                $day = trim($columns->eq(0)->text());
                $time = trim($columns->eq(1)->text());
                $titleElement = $columns->eq(2)->filter('a');

                $title = $titleElement->count() ? trim($titleElement->text()) : trim($columns->eq(2)->text());
                $link = $titleElement->count() ? $titleElement->attr('href') : null;
                $scene = trim($columns->eq(3)->text());

                // Convert day and time into MSSQL-compatible datetime (YYYY-MM-DD HH:MM:SS)
                $dayNumber = preg_replace('/[^\d]/', '', $day); // Extracts only numbers from "Pt 07"
                $dateTime = "{$year}-{$month}-" . str_pad($dayNumber, 2, '0', STR_PAD_LEFT) . " " . $time . ":00";

                $events[] = [
                    'datetime' => $dateTime, // Ready for MSSQL
                    'title' => $title,
                    'link' => $link ? "https://www.teatrplock.pl{$link}" : null, // Absolute link
                    'scene' => $scene,
                ];
            }
        });

        return new JsonResponse($events, Response::HTTP_OK);
    }
}
