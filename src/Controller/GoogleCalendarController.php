<?php
namespace App\Controller;

use App\Repository\GoogleIntegrationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;

class GoogleCalendarController extends AbstractController
{

    public function __construct(private GoogleIntegrationRepository $googleIntegrationRepository)
    {
    }
    #[Route('/api/v1/google/calendar/events', name: 'google_calendar_event', methods: ['POST'])]
    public function addEventToGoogleCalendar(Request $request): Response
    {
        // Retrieve request data
        $data = json_decode($request->getContent(), true);

        // Validate input
        if (!isset($data['title'], $data['description'], $data['date'], $data['location'])) {
            return $this->json(['error' => 'Missing required fields.'], 400);
        }

        // Retrieve the authenticated user
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'User not authenticated.'], 401);
        }

        // Get Google integration from the user
        $googleIntegration = $user->getGoogleIntegration();
        if (!$googleIntegration) {
            return $this->json(['error' => 'Google account not connected.'], 401);
        }

        // Set up the Google API client
        $client = new Google_Client();
        $client->setClientId($_ENV['OAUTH_GOOGLE_CLIENT_ID']);
        $client->setClientSecret($_ENV['OAUTH_GOOGLE_CLIENT_SECRET']);
        $client->setRedirectUri('http://127.0.0.1:8000/connect/google/check');
        $client->addScope(Google_Service_Calendar::CALENDAR);
        $client->setAccessToken($googleIntegration->getAccessToken());

        // Handle token expiration
        if ($client->isAccessTokenExpired()) {
            $refreshToken = $googleIntegration->getRefreshToken();
            if ($refreshToken) {
                $client->fetchAccessTokenWithRefreshToken($refreshToken);

                // Update the access token in the database
                $newAccessToken = $client->getAccessToken();
                $googleIntegration->setAccessToken($newAccessToken['access_token']);

                $this->googleIntegrationRepository->save($googleIntegration, true);

            } else {
                return $this->json(['error' => 'Refresh token missing or invalid.'], 401);
            }
        }

        // Initialize Google Calendar service
        $calendarService = new Google_Service_Calendar($client);

        // Create the event
        $event = new Google_Service_Calendar_Event([
            'summary' => $data['title'],
            'description' => $data['description'],
            'start' => [
                'dateTime' => $data['date'],
                'timeZone' => 'Europe/Warsaw',
            ],
            'end' => [
                'dateTime' => $data['date'], // Adjust end time if needed
                'timeZone' => 'Europe/Warsaw',
            ],
            'location' => $data['location'],
        ]);

        try {
            $calendarService->events->insert('primary', $event);
            return $this->json(['message' => 'Event successfully created.'], 201);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
}