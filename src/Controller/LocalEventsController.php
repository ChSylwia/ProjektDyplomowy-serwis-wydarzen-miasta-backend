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
        $data = $request->request->all();
        $uploadedFile = $request->files->get('image');

        if (!$data['title'] || !$data['description'] || !$data['date']) {
            return $this->json(['error' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);
        }

        if (!$uploadedFile) {
            return $this->json(['error' => 'Image is required'], Response::HTTP_BAD_REQUEST);
        }

        $eventDate = new \DateTime($data['date']);
        $currentDate = new \DateTime();
        if ($eventDate < $currentDate) {
            return $this->json(['error' => 'Data wydarzenia musi być w przyszłości.'], Response::HTTP_BAD_REQUEST);
        }

        $uploadsDir = $this->getParameter('upload_dir');
        $fileName = uniqid() . '.' . $uploadedFile->guessExtension();

        try {
            $uploadedFile->move($uploadsDir, $fileName);
        } catch (\Exception $e) {
            return $this->json(['error' => 'File upload failed'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $priceMin = isset($data['priceMin']) && $data['priceMin'] !== "" ? (float) $data['priceMin'] : null;
        $priceMax = isset($data['priceMax']) && $data['priceMax'] !== "" ? (float) $data['priceMax'] : null;

        $link = isset($data['link']) && $data['link'] !== "" ? $data['link'] : null;

        // Sprawdzamy czy priceMin nie jest większe niż priceMax
        if ($priceMin !== null && $priceMax !== null && $priceMin > $priceMax) {
            return $this->json(['error' => 'Minimalna cena nie może być większa niż maksymalna cena.'], Response::HTTP_BAD_REQUEST);
        }

        $currentUser = $this->getUser();

        // Create new LocalEvent
        $event = new LocalEvents();
        $event->setUser($currentUser);
        $event->setTitle($data['title']);
        $event->setDescription($data['description']);
        $event->setDate($eventDate);
        $event->setPriceMin($priceMin);
        $event->setPriceMax($priceMax);
        $event->setLink($link);
        $event->setImage($request->getSchemeAndHttpHost() . '/uploads/' . $fileName);
        $event->setTypeEvent("local-event");
        $event->setCategory($data['category'] === '' ? 'inne' : $data['category']);

        $entityManager->persist($event);
        $entityManager->flush();

        return $this->json(['message' => 'Event created successfully', 'event' => $event, 'ok' => true], Response::HTTP_CREATED);
    }



    #[Route('/', name: 'local_events_list', methods: ['GET'])]
    public function list(LocalEventsRepository $repository): Response
    {
        $currentUser = $this->getUser();
        $events = $repository->createQueryBuilder('e')
            ->where('e.user = :user')
            ->andWhere('e.deleted = :deleted OR e.deleted IS NULL')
            ->setParameter('user', $currentUser)
            ->setParameter('deleted', false)
            ->getQuery()
            ->getResult();
        $formattedEvents = array_map(function ($event) {
            return [
                'id' => $event->getId(),
                'title' => $event->getTitle(),
                'description' => $event->getDescription(),
                'date' => $event->getDate()->format('Y-m-d H:i:s'),
                'image' => $event->getImage() ? $event->getImage() : null,
                'category' => $event->getCategory(),
            ];
        }, $events);

        return $this->json([$formattedEvents, 'ok' => true], Response::HTTP_OK);
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

    #[Route('/{id}/edit', name: 'local_events_edit', methods: ['POST'])]
    public function update(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        LocalEventsRepository $repository
    ): Response {
        $event = $repository->find($id);
        if (!$event) {
            return $this->json(['error' => 'Event not found'], Response::HTTP_NOT_FOUND);
        }

        $data = $request->request->all();  // Pobiera dane z formularza


        // Walidacja daty - musi być przyszła
        if (isset($data['date'])) {
            $eventDate = new \DateTime($data['date']);
            $currentDate = new \DateTime();
            if ($eventDate < $currentDate) {
                return $this->json(['error' => 'Data wydarzenia musi być w przyszłości.'], Response::HTTP_BAD_REQUEST);
            }
            $event->setDate($eventDate);
        }
        $priceMin = $request->request->get('priceMin') !== null ? (float) $request->request->get('priceMin') : null;
        $priceMax = $request->request->get('priceMax') !== null ? (float) $request->request->get('priceMax') : null;


        if ($priceMin !== null && $priceMax !== null && $priceMin > $priceMax) {
            return $this->json(['error' => 'Minimalna cena nie może być większa niż maksymalna cena.'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['title'])) {
            $event->setTitle($data['title']);
        }
        if (isset($data['description'])) {
            $event->setDescription($data['description']);
        }
        if (isset($data['link'])) {
            $event->setLink($data['link']);
        }
        if (isset($data['category'])) {
            $event->setCategory($data['category']);
        }
        $uploadedFile = $request->files->get('image');

        if ($uploadedFile) { // Sprawdzamy, czy plik faktycznie został przesłany
            $uploadsDir = $this->getParameter('upload_dir');
            $fileName = uniqid() . '.' . $uploadedFile->guessExtension();

            try {
                $uploadedFile->move($uploadsDir, $fileName);
                $event->setImage($request->getSchemeAndHttpHost() . '/uploads/' . $fileName);
            } catch (\Exception $e) {
                return $this->json(['error' => 'File upload failed'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }


        $event->setPriceMin($priceMin);
        $event->setPriceMax($priceMax);

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
