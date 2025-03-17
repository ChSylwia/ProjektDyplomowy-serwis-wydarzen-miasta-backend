<?php

namespace App\Controller;

use Aws\S3\S3Client;
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
                'body' => ['title', 'description', 'date', 'price', 'link', 'image', 'typeEvent', 'category'],
            ],
            'get' => [
                'query' => [],
                'body' => []
            ],
            'edit' => [
                'query' => [],
                'body' => ['title', 'description', 'date', 'price', 'link', 'image', 'category'],
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
        $s3Client = new S3Client([
            'version' => 'latest',
            'region' => getenv('AWS_DEFAULT_REGION'),
            'credentials' => [
                'key' => getenv('AWS_ACCESS_KEY_ID'),
                'secret' => getenv('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);

        $fileName = uniqid() . '.' . $uploadedFile->guessExtension();
        $bucket = 'chwile-plocka'; // Your bucket name
        $uploadsDir = 'uploads/' . $fileName; // Folder in bucket

        try {
            // Upload file to S3
            $result = $s3Client->putObject([
                'Bucket' => $bucket,
                'Key' => $uploadsDir,
                'Body' => fopen($uploadedFile->getPathname(), 'rb'),
                'ContentType' => $uploadedFile->getMimeType()
            ]);
            $imageUrl = $result['ObjectURL']; // Get public URL of the uploaded image

        } catch (\Exception $e) {
            return $this->json(['error' => 'File upload failed: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
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
        $event->setImage($imageUrl);
        $event->setTypeEvent("local-event");

        // Process the category data to support multiple categories
        $categories = $data['category'] ?? null; // Use null if not provided
        if (!$categories || (is_string($categories) && trim($categories) === '')) {
            // If no valid category data provided, default to ['inne']
            $categories = ['inne'];
        } elseif (!is_array($categories)) {
            // If a string is provided, assume comma-separated values
            $categories = explode(',', $categories);
            $categories = array_map('trim', $categories);
            // Remove any empty values
            $categories = array_filter($categories, fn($cat) => $cat !== '');
            $categories = array_values($categories);
            // If the resulting array is empty, default to ['inne']
            if (empty($categories)) {
                $categories = ['inne'];
            }
        }
        $event->setCategory($categories);



        $entityManager->persist($event);
        $entityManager->flush();

        return $this->json(['message' => 'Event created successfully', 'event' => $event, 'ok' => true], Response::HTTP_CREATED);
    }

    #[Route('/', name: 'local_events_list', methods: ['GET'])]
    public function list(LocalEventsRepository $repository): Response
    {
        $currentUser = $this->getUser();
        /**
         * @var array<array-key,LocalEvents>
         */
        $events = $repository->createQueryBuilder('e')
            ->where('e.user = :user')
            ->andWhere('e.deleted = :deleted OR e.deleted IS NULL')
            ->setParameter('user', $currentUser)
            ->setParameter('deleted', false)
            ->getQuery()
            ->getResult();

        $formattedEvents = array_map(function (LocalEvents $event) {
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
            $categories = $data['category'] ?? null;
            if (!$categories || (is_string($categories) && trim($categories) === '')) {
                $categories = ['inne'];
            } elseif (!is_array($categories)) {
                $categories = explode(',', $categories);
                $categories = array_map('trim', $categories);
                $categories = array_filter($categories, fn($cat) => $cat !== '');
                $categories = array_values($categories);
                if (empty($categories)) {
                    $categories = ['inne'];
                }
            }
            $event->setCategory($categories);
        }
        $s3Client = new S3Client([
            'version' => 'latest',
            'region' => getenv('AWS_DEFAULT_REGION'),
            'credentials' => [
                'key' => getenv('AWS_ACCESS_KEY_ID'),
                'secret' => getenv('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);


        $uploadedFile = $request->files->get('image');
        if ($uploadedFile) { // Check if the file was actually uploaded
            $fileName = uniqid() . '.' . $uploadedFile->guessExtension();
            $bucket = 'chwile-plocka'; // Your S3 bucket name
            $uploadsDir = 'uploads/' . $fileName; // S3 path inside bucket

            try {
                // Upload file to S3
                $result = $s3Client->putObject([
                    'Bucket' => $bucket,
                    'Key' => $uploadsDir,
                    'Body' => fopen($uploadedFile->getPathname(), 'rb'),
                    'ContentType' => $uploadedFile->getMimeType(),
                ]);

                // Set public S3 URL as image path in entity
                $event->setImage($result['ObjectURL']);

            } catch (\Exception $e) {
                return $this->json(['error' => 'File upload failed: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
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
