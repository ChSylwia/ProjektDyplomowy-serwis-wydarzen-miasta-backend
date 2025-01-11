<?php

namespace App\Controller;

use App\Entity\LocalEvents;
use App\Repository\LocalEventsRepository;
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
    public function list(LocalEventsRepository $repository): Response
    {
        // Fetch all events from the repository
        $events = $repository->findAll();

        return $this->json([
            'events' => $events,
            'ok' => true
        ], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'all_local_events_show', methods: ['GET'])]
    public function show(int $id, LocalEventsRepository $repository): Response
    {

        $event = $repository->find($id);

        if (!$event) {
            return $this->json(['error' => 'Event not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([$event, 'ok' => true], Response::HTTP_OK);
    }


}
