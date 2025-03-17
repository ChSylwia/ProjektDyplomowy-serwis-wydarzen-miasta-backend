<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GoogleController extends AbstractController
{
    public function __construct(private HttpClientInterface $httpClient)
    {
    }

    #[Route('/auth/google', name: 'auth_google')]
    public function googleAuth(): RedirectResponse
    {
        // Retrieve credentials from the environment
        $clientId = $_ENV['OAUTH_GOOGLE_CLIENT_ID'];
        $redirectUri = 'https://chwileplocka-backend-72c2516b9445.herokuapp.com/connect/google/check';
        $scopes = 'openid profile https://www.googleapis.com/auth/calendar.events https://www.googleapis.com/auth/calendar https://www.googleapis.com/auth/userinfo.email';

        $authUrl = sprintf(
            'https://accounts.google.com/o/oauth2/v2/auth?response_type=code&client_id=%s&redirect_uri=%s&scope=%s&access_type=offline&prompt=consent',
            urlencode($clientId),
            urlencode($redirectUri),
            urlencode($scopes)
        );
        return new RedirectResponse($authUrl);
    }

    #[Route('/connect/google', name: 'connect_google_start')]
    public function connect(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry->getClient('google')->redirect(['email', 'profile', 'openid'], []);
    }

    #[Route('/connect/google/check', name: 'google_check')]
    public function googleCheck(Request $request): JsonResponse
    {
        // Force JSON response format
        $request->setRequestFormat('json');

        // Retrieve the authorization code
        $code = $request->query->get('code');
        if (!$code) {
            return new JsonResponse(['error' => 'Missing authorization code'], 400);
        }

        $clientId = $_ENV['OAUTH_GOOGLE_CLIENT_ID'];
        $clientSecret = $_ENV['OAUTH_GOOGLE_CLIENT_SECRET'];
        $redirectUri = 'https://chwileplocka-backend-72c2516b9445.herokuapp.com/connect/google/check';

        try {
            // Exchange authorization code for tokens
            $tokenResponse = $this->httpClient->request('POST', 'https://oauth2.googleapis.com/token', [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => [
                    'code' => $code,
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'redirect_uri' => $redirectUri,
                    'grant_type' => 'authorization_code',
                ],
            ]);
            $tokenData = json_decode($tokenResponse->getContent(), true);

            if (isset($tokenData['error'])) {
                return new JsonResponse(['error' => $tokenData['error_description']], 400);
            }

            // Debugging: Log the token response

            // Check if id_token is present
            if (empty($tokenData['id_token'])) {
                return new JsonResponse([
                    'error' => 'No ID token provided by Google.',
                    'details' => $tokenData,
                ], 500);
            }

            $idToken = $tokenData['id_token'];
            $accessToken = $tokenData['access_token'];

            // Optionally, you could validate the ID token (e.g., verify signature and claims)

            // Return token(s) to frontend
            return new JsonResponse([
                'access_token' => $accessToken,
                'id_token' => $idToken,
            ], 200);

        } catch (\Exception $e) {
            // Log and return error
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
