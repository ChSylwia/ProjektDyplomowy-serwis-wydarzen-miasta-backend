<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class GoogleAuthenticator extends AbstractAuthenticator
{
    private HttpClientInterface $httpClient;

    public function __construct(
        HttpClientInterface $httpClient,
        private UserRepository $userRepository,
        private JWTTokenManagerInterface $jwtManager
    ) {
        $this->httpClient = $httpClient;
    }

    public function supports(Request $request): ?bool
    {
        return $request->getPathInfo() === '/connect/google/check' && $request->query->has('code');
    }

    public function authenticate(Request $request): Passport
    {
        $code = $request->query->get('code');
        if (!$code) {
            throw new \RuntimeException('No authorization code provided.');
        }
        $clientId = $_ENV['OAUTH_GOOGLE_CLIENT_ID'];
        $clientSecret = $_ENV['OAUTH_GOOGLE_CLIENT_SECRET'];
        $redirectUri = 'http://127.0.0.1:8000/connect/google/check';

        $response = $this->httpClient->request('POST', 'https://oauth2.googleapis.com/token', [
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

        $statusCode = $response->getStatusCode();
        // $responseContent = $response->getContent(false); // Disable exception for non-200 responses
        // file_put_contents('google_auth_debug.log', "HTTP Status: $statusCode\nResponse: $responseContent\n", FILE_APPEND);

        if ($statusCode !== 200) {
            throw new \RuntimeException("Failed to exchange code for token. HTTP Status: $statusCode");
        }

        $tokenData = json_decode($response->getContent(), true);

        if (!isset($tokenData['id_token'])) {
            throw new \RuntimeException('No ID token returned from Google.');
        }

        $idToken = $tokenData['id_token'];

        return new SelfValidatingPassport(new UserBadge($idToken, function ($idToken) {
            $decodedToken = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], explode('.', $idToken)[1])), true);

            if (!isset($decodedToken['email'])) {
                throw new \RuntimeException('No email found in ID token.');
            }

            $email = $decodedToken['email'];

            $user = $this->userRepository->findOneBy(['email' => $email]);
            if (!$user) {
                $user = new User();
                $user->setEmail($email);
                $user->setPassword('google_user_placeholder');
                $user->setFirstName($decodedToken['given_name'] ?? null);
                $user->setLastName($decodedToken['family_name'] ?? null);
                $user->setUsername($decodedToken['name'] ?? null); // Fallback to full name if username is not separate
                $user->setCity(null); // Google does not provide city info directly
                $user->setPostalCode(postalCode: null); // Google does not provide postal code directly
                $user->setUserType('private'); // Default user type for Google
                $user->setTermsAccepted(true);
                $this->userRepository->save($user, flush: true);

            }

            return $user;
        }));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Retrieve the authenticated user
        $user = $token->getUser();

        // Generate JWT token using lexik/jwt-authentication-bundle
        $jwtToken = $this->jwtManager->create($user);

        // Redirect to the frontend with the token
        $redirectUrl = sprintf('http://localhost:5173/success?token=%s', $jwtToken);

        return new RedirectResponse($redirectUrl);
    }
    public function onAuthenticationFailure(Request $request, \Throwable $exception): ?Response
    {
        return new Response('Authentication failed: ' . $exception->getMessage(), Response::HTTP_UNAUTHORIZED);
    }
}
