<?php

namespace App\Controller;

use App\Entity\LocalEvents;
use App\Repository\LocalEventsRepository;
use App\Repository\EventsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Factory\JsonResponseFactory;
use App\Services\ApiService;
#[Route('/api/v1/all-local-events')]
class AllLocalEventsController extends AbstractController
{
    private array $allowedProperties;
    private array $requiredFields;

    public function __construct(private JsonResponseFactory $jsonResponseFactory, private ApiService $apiService)
    {
        $this->allowedProperties = [
            'find' => [
                'query' => ['page', 'limit'],
                'body' => []
            ],
            'create' => [
                'query' => [],
                'body' => ['title', 'description', 'date', 'price', 'link', 'image'],
            ],
            'get' => [
                'query' => [],
                'body' => []
            ],
            'edit' => [
                'query' => [],
                'body' => ['title', 'description', 'date', 'price', 'link', 'image'],
            ],
            'delete' => [
                'query' => [],
                'body' => []
            ]
        ];
        $this->requiredFields = [
            'find' => [
                'query' => [],
                'body' => []
            ],
            'create' => [
                'query' => [],
                'body' => ['title', 'description', 'date', 'price', 'link', 'image'],
            ],
            'get' => [
                'query' => [],
                'body' => []
            ],
            'edit' => [
                'query' => [],
                'body' => []
            ],
            'delete' => [
                'query' => [],
                'body' => []
            ]
        ];
    }

    #[Route('/', name: 'all_local_events_list', methods: ['GET'])]
    public function list(LocalEventsRepository $localEventsRepository, EventsRepository $repository): Response
    {
        $currentDate = new \DateTime(); // Get current date and time

        // Fetch all events from the repositories
        $events = $localEventsRepository->findAll();
        $events2 = $repository->findAll();

        // Filter out events that have expired (date in the past)
        $events = array_filter($events, fn($event) => $event->getDate() >= $currentDate && !$event->getDeleted());
        $events2 = array_filter($events2, fn($event) => $event->getDate() >= $currentDate);

        // Add markers to each event
        $localEvents = array_map(fn($event) => [
            'id' => 'local-' . $event->getId(),
            'event' => $event
        ], $events);

        $globalEvents = array_map(fn($event) => [
            'id' => 'global-' . $event->getId(),
            'event' => $event
        ], $events2);

        return $this->json([
            'events' => array_merge($localEvents, $globalEvents),
            'ok' => true
        ], Response::HTTP_OK);
    }


    #[Route('/{id}', name: 'all_local_events_show', methods: ['GET'])]
    public function show(int $id, LocalEventsRepository $localEventsRepository, EventsRepository $repository): Response
    {
        // Try to find the event in the local events repository
        $event = $localEventsRepository->find($id);

        // If not found in the local repository, try to find it in the global repository
        if (!$event) {
            $event = $repository->find($id);
            // If not found in either repository, return an error
            if (!$event) {
                return $this->json(['error' => 'Event not found'], Response::HTTP_NOT_FOUND);
            }
            // Mark the event as global
            $eventType = 'global';
        } else {
            // Mark the event as local
            $eventType = 'local';
        }

        return $this->json([$event, 'ok' => true], Response::HTTP_OK);
    }


}
