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
#[Route('/api/v1/local-events')]
class LocalEventsController extends AbstractController
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
                'body' => ['title', 'description', 'date', 'price', 'link', 'image', 'typeEvent'],
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
                'body' => ['title', 'description', 'date', 'price', 'link', 'image', 'typeEvent'],
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

    #[Route('/create', name: 'local_events_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $data = json_decode($request->getContent(), true);

        // Validation
        if (!$data['title'] || !$data['description'] || !$data['date']) {
            return $this->json(['error' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);
        }
        $price = $data['price'];
        $price_value = $price === "" ? null : $price;
        $link = $data['link'];
        $link_value = $link === "" ? null : $link;

        $currentUser = $this->getUser();

        // Create new LocalEvent
        $event = new LocalEvents();
        $event->setUser($currentUser);
        $event->setTitle($data['title']);
        $event->setDescription($data['description']);
        $event->setDate(new \DateTime($data['date']));
        $event->setPrice($price_value);
        $event->setLink($link_value);
        $event->setImage($data['image'] ?? null);
        $event->setTypeEvent("local-event");
        $entityManager->persist($event);
        $entityManager->flush();

        return $this->json(['message' => 'Event created successfully', 'event' => $event, 'ok' => true], Response::HTTP_CREATED);
    }
    #[Route('/', name: 'local_events_list', methods: ['GET'])]
    public function list(LocalEventsRepository $repository): Response
    {
        $currentUser = $this->getUser();

        // Modify the query to exclude events that are marked as deleted
        $events = $repository->createQueryBuilder('e')
            ->where('e.user = :user')
            ->andWhere('e.deleted = :deleted OR e.deleted IS NULL')
            ->setParameter('user', $currentUser)
            ->setParameter('deleted', false)
            ->getQuery()
            ->getResult();

        return $this->json([$events, 'ok' => true], Response::HTTP_OK);
    }


    #[Route('/{id}', name: 'local_events_show', methods: ['GET'])]
    public function show(int $id, LocalEventsRepository $repository): Response
    {

        $event = $repository->find($id);

        if (!$event) {
            return $this->json(['error' => 'Event not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([$event, 'ok' => true], Response::HTTP_OK);
    }

    #[Route('/{id}/edit', name: 'local_events_edit', methods: ['PUT'])]
    public function update(int $id, Request $request, EntityManagerInterface $entityManager, LocalEventsRepository $repository): Response
    {
        $data = json_decode($request->getContent(), true);
        $event = $repository->find($id);

        if (!$event) {
            return $this->json(['error' => 'Event not found'], Response::HTTP_NOT_FOUND);
        }

        if (isset($data['title'])) {
            $event->setTitle($data['title']);
        }
        if (isset($data['description'])) {
            $event->setDescription($data['description']);
        }
        if (isset($data['date'])) {
            $event->setDate(new \DateTime($data['date']));
        }
        if (isset($data['price'])) {
            $event->setPrice($data['price']);
        }
        if (isset($data['link'])) {
            $event->setLink($data['link']);
        }
        if (isset($data['image'])) {
            $event->setImage($data['image']);
        }

        $entityManager->flush();

        return $this->json(['message' => 'Event updated successfully', 'event' => $event, 'ok' => true], Response::HTTP_OK);
    }
    #[Route('/{id}', name: 'local_events_delete', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $entityManager, LocalEventsRepository $repository): Response
    {
        $event = $repository->find($id);

        if (!$event) {
            return $this->json(['error' => 'Event not found'], Response::HTTP_NOT_FOUND);
        }

        // Set the "deleted" flag to true instead of removing the event
        $event->setDeleted(true);
        $entityManager->flush();

        return $this->json(['message' => 'Event marked as deleted successfully', 'ok' => true], Response::HTTP_OK);
    }

}
