<?php

namespace App\Security;

use App\Entity\User;
use App\Entity\GoogleIntegration;
use App\Repository\GoogleIntegrationRepository;
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
use Google\Client as GoogleClient;
class GoogleAuthenticator extends AbstractAuthenticator
{
    private HttpClientInterface $httpClient;

    public function __construct(
        HttpClientInterface $httpClient,
        private UserRepository $userRepository,
        private JWTTokenManagerInterface $jwtManager,
        private GoogleIntegrationRepository $googleIntegrationRepository
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
        $redirectUri = 'https://chwileplocka-backend-72c2516b9445.herokuapp.com/connect/google/check';

        $tokenData = null;

        try {
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

            if ($response->getStatusCode() !== 200) {
                throw new \RuntimeException('Failed to exchange code for token. Status: ' . $response->getStatusCode());
            }

            $tokenData = json_decode($response->getContent(), true);

            if (!isset($tokenData['id_token'])) {
                throw new \RuntimeException('No ID token returned from Google.');
            }
        } catch (\Exception $e) {
            throw new \RuntimeException('Error during token exchange: ' . $e->getMessage());
        }

        $idToken = $tokenData['id_token'];

        return new SelfValidatingPassport(new UserBadge($idToken, function ($idToken) use ($tokenData) {
            $decodedToken = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], explode('.', $idToken)[1])), true);
            file_put_contents('google_token.log', print_r($decodedToken, true)); // Log the decoded token for inspection

            if (!isset($decodedToken['email'])) {
                throw new \RuntimeException('No email found in ID token.');
            }

            if (!isset($decodedToken['sub'])) {
                throw new \RuntimeException('No Google ID (sub) found in ID token.');
            }

            $email = $decodedToken['email'];
            $googleId = $decodedToken['sub'];

            $user = $this->userRepository->findOneBy(['email' => $email]);
            if (!$user) {
                $user = new User();
                $user->setEmail($email);
                $user->setPassword('google_user_placeholder'); // Placeholder password
                $user->setFirstName($decodedToken['given_name'] ?? 'Google');
                $user->setLastName($decodedToken['family_name'] ?? 'User');
                $user->setUsername($decodedToken['name'] ?? 'Google User');
                $user->setUserType('private');
                $user->setTermsAccepted(true);
                $this->userRepository->save($user, true);
            }

            $googleIntegration = $user->getGoogleIntegration();
            if (!$googleIntegration) {
                $googleIntegration = new GoogleIntegration();
                $user->setGoogleIntegration($googleIntegration);
            }

            $googleIntegration->setGoogleId($googleId);
            $googleIntegration->setAccessToken($tokenData['access_token']);
            $googleIntegration->setRefreshToken($tokenData['refresh_token'] ?? null);


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
