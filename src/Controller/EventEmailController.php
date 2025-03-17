<?php

namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EventEmailController extends AbstractController
{
    #[Route('/api/v1/sendEventEmail', name: 'send_event_email', methods: ['POST'])]
    public function sendEventEmail(Request $request, MailerInterface $mailer): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse((object) ['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $requiredFields = ['title', 'description', 'date', 'location'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return new JsonResponse((object) ['error' => "Missing field: $field"], Response::HTTP_BAD_REQUEST);
            }
        }
        $user = $this->getUser();
        if (!$user || !$user->getEmail()) {
            return $this->json(['error' => 'User email not found.'], Response::HTTP_UNAUTHORIZED);
        }
        $email = (new Email())
            ->from('hello@demomailtrap.com')
            ->to($user->getEmail())
            ->subject('New Event: ' . $data['title'])
            ->text(
                "Event: {$data['title']}\n" .
                "Description: {$data['description']}\n" .
                "Date: {$data['date']}\n" .
                "Location: {$data['location']}"
            )
            ->html(
                "<h1>{$data['title']}</h1>" .
                "<p>{$data['description']}</p>" .
                "<p>Date: {$data['date']}</p>" .
                "<p>Location: {$data['location']}</p>"
            );

        try {
            $mailer->send($email);
            return new JsonResponse((object) ['message' => 'Email sent successfully.'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(
                (object) ['error' => 'Failed to send email', 'details' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }



    /*#[Route('/api/v1/send-event-email', name: 'send_event_email', methods: ['POST'])]
    public function sendEventEmail(Request $request, MailerInterface $mailer): Response
    {

        // Decode JSON
        $data = json_decode($request->getContent(), true);
        if (!isset($data['title'], $data['description'], $data['date'], $data['location'])) {
            return $this->json(['error' => 'Missing required fields.'], Response::HTTP_BAD_REQUEST);
        }

        // Get user email
        $user = $this->getUser();
        if (!$user || !$user->getEmail()) {
            return $this->json(['error' => 'User email not found.'], Response::HTTP_UNAUTHORIZED);
        }

        // Create Email
        $email = (new Email())
            ->from('mailtrap@example.com')
            ->to($user->getEmail())
            ->subject('Event Notification')
            ->text('Your event details are here.')
            ->html('<p>Your event details are here.</p>');

        try {
            $mailer->send($email);
            return $this->json(['message' => 'Email sent successfully.'], Response::HTTP_OK);
        } catch (TransportExceptionInterface $e) {
            error_log("Sendmail Error: " . $e->getMessage());
            return $this->json(['error' => 'Email sending failed: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    #[Route('/api/v1/sendEventEmail', name: 'send_event_email', methods: ['POST'])]
    public function sendEventEmail(Request $request, MailerInterface $mailer): Response
    {
        $user = $this->getUser();
        if (!$user || !$user->getEmail()) {
            return $this->json(['error' => 'User email not found.'], Response::HTTP_UNAUTHORIZED);
        }
        $email = (new Email())
            ->from('hello@demomailtrap.com')
            ->to('23@gmail.com')
            ->subject('Event Notification')
            ->text('Your event details are here.')
            ->html('<p>Your event details are here.</p>');

        try {
            $mailer->send($email);
            return $this->json(['message' => 'Email sent successfully.'], Response::HTTP_OK);
        } catch (TransportExceptionInterface $e) {
            error_log("Sendmail Error: " . $e->getMessage());
            return $this->json(['error' => 'Email sending failed: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
*/

}
