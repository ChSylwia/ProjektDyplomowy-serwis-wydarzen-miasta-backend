<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\LocalEvents;
use App\Entity\Events;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('api/v1/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{

    /**
     * This endpoint can serve as your admin dashboard.
     *
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     */

    #[Route('/', name: 'admin_home', methods: ['GET'])]
    public function home(): JsonResponse
    {
        return $this->json([
            'error' => false,
            'message' => 'Welcome to the Admin Home',
        ]);
    }

    #[Route('/dashboard', name: 'admin_dashboard', methods: ['GET'])]
    public function dashboard(EntityManagerInterface $entityManager): JsonResponse
    {
        // Example: Fetch all events (or any other admin-specific data)
        $events = $entityManager->getRepository(Events::class)->findAll();

        return $this->json([
            'error' => false,
            'message' => 'Admin dashboard data retrieved successfully',
            'data' => [
                'events' => $events,
                // You can add more admin data here
            ]
        ]);
    }

    #[Route('/users', name: 'admin_users_list', methods: ['GET'])]
    public function listUsers(EntityManagerInterface $em): JsonResponse
    {
        $users = $em->getRepository(User::class)->findAll();
        $data = [];
        foreach ($users as $user) {
            $data[] = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'roles' => $user->getRoles(),
                // Add other fields if needed
            ];
        }

        return new JsonResponse($data);
    }

    /**
     * Update a user.
     */
    #[Route('/users/{id}', name: 'admin_users_update', methods: ['POST'])]
    public function updateUser(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $em->getRepository(User::class)->find($id);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        // Example: update first name, last name, and roles if provided.
        if (isset($data['firstName'])) {
            $user->setFirstName($data['firstName']);
        }
        if (isset($data['lastName'])) {
            $user->setLastName($data['lastName']);
        }
        if (isset($data['roles']) && is_array($data['roles'])) {
            $user->setRoles($data['roles']);
        }
        // Update additional fields as necessary.

        $em->flush();

        return new JsonResponse(['status' => 'User updated successfully']);
    }

    /**
     * Delete a user.
     */
    #[Route('/users/{id}', name: 'admin_users_delete', methods: ['DELETE'])]
    public function deleteUser(int $id, EntityManagerInterface $em): JsonResponse
    {
        $user = $em->getRepository(User::class)->find($id);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        $em->remove($user);
        $em->flush();

        return new JsonResponse(['status' => 'User deleted successfully']);
    }

    /*
     * ===============================
     *      LOCAL EVENTS Endpoints
     * ===============================
     */

    /**
     * List all local events.
     */
    #[Route('/local-events', name: 'admin_local_events_list', methods: ['GET'])]
    public function listLocalEvents(EntityManagerInterface $em): JsonResponse
    {
        $localEvents = $em->getRepository(LocalEvents::class)->findAll();
        $data = [];
        foreach ($localEvents as $event) {
            $data[] = [
                'id' => $event->getId(),
                'title' => $event->getTitle(),
                'description' => $event->getDescription(),
                'date' => $event->getDate() ? $event->getDate()->format('Y-m-d H:i:s') : null,
                'priceMin' => $event->getPriceMin(),
                'priceMax' => $event->getPriceMax(),
                'link' => $event->getLink(),
                'typeEvent' => $event->getTypeEvent(),
                'category' => $event->getCategory(),
                'deleted' => $event->getDeleted(),
            ];
        }

        return new JsonResponse($data);
    }

    /**
     * Update a local event.
     */
    #[Route('/local-events/{id}', name: 'admin_local_events_update', methods: ['POST'])]
    public function updateLocalEvent(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $event = $em->getRepository(LocalEvents::class)->find($id);
        if (!$event) {
            return new JsonResponse(['error' => 'Local event not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        if (isset($data['title'])) {
            $event->setTitle($data['title']);
        }
        if (isset($data['description'])) {
            $event->setDescription($data['description']);
        }
        if (isset($data['date'])) {
            $event->setDate(new \DateTime($data['date']));
        }
        if (isset($data['priceMin'])) {
            $event->setPriceMin($data['priceMin']);
        }
        if (isset($data['priceMax'])) {
            $event->setPriceMax($data['priceMax']);
        }
        if (isset($data['link'])) {
            $event->setLink($data['link']);
        }
        if (isset($data['typeEvent'])) {
            $event->setTypeEvent($data['typeEvent']);
        }
        if (isset($data['category'])) {
            $event->setCategory($data['category']);
        }
        if (isset($data['deleted'])) {
            $event->setDeleted($data['deleted']);
        }
        // Update additional fields as needed.

        $em->flush();

        return new JsonResponse(['status' => 'Local event updated successfully']);
    }

    /**
     * Delete a local event.
     */
    #[Route('/local-events/{id}', name: 'admin_local_events_delete', methods: ['DELETE'])]
    public function deleteLocalEvent(int $id, EntityManagerInterface $em): JsonResponse
    {
        $event = $em->getRepository(LocalEvents::class)->find($id);
        if (!$event) {
            return new JsonResponse(['error' => 'Local event not found'], 404);
        }

        $em->remove($event);
        $em->flush();

        return new JsonResponse(['status' => 'Local event deleted successfully']);
    }

    /*
     * ===============================
     *         GLOBAL EVENTS Endpoints
     * ===============================
     */

    /**
     * List all global events.
     */
    #[Route('/events', name: 'admin_events_list', methods: ['GET'])]
    public function listEvents(EntityManagerInterface $em): JsonResponse
    {
        $events = $em->getRepository(Events::class)->findAll();
        $data = [];
        foreach ($events as $event) {
            $data[] = [
                'id' => $event->getId(),
                'external_id' => $event->getExternalId(),
                'source' => $event->getSource(),
                'image' => $event->getImage(),
                'title' => $event->getTitle(),
                'description' => $event->getDescription(),
                'date' => $event->getDate() ? $event->getDate()->format('Y-m-d H:i:s') : null,
                'price' => $event->getPrice(),
                'link' => $event->getLink(),
                'typeEvent' => $event->getTypeEvent(),
                'category' => $event->getCategory(),
            ];
        }

        return new JsonResponse($data);
    }

    /**
     * Update a global event.
     */
    #[Route('/events/{id}', name: 'admin_events_update', methods: ['POST'])]
    public function updateEvent(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $event = $em->getRepository(Events::class)->find($id);
        if (!$event) {
            return new JsonResponse(['error' => 'Event not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
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
        if (isset($data['typeEvent'])) {
            $event->setTypeEvent($data['typeEvent']);
        }
        if (isset($data['category'])) {
            $event->setCategory($data['category']);
        }
        if (isset($data['image'])) {
            $event->setImage($data['image']);
        }
        // Update additional fields as needed.

        $em->flush();

        return new JsonResponse(['status' => 'Event updated successfully']);
    }

    /**
     * Delete a global event.
     */
    #[Route('/events/{id}', name: 'admin_events_delete', methods: ['DELETE'])]
    public function deleteEvent(int $id, EntityManagerInterface $em): JsonResponse
    {
        $event = $em->getRepository(Events::class)->find($id);
        if (!$event) {
            return new JsonResponse(['error' => 'Event not found'], 404);
        }

        $em->remove($event);
        $em->flush();

        return new JsonResponse(['status' => 'Event deleted successfully']);
    }
}
