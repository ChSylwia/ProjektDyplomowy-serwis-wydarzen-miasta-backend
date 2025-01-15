<?php

namespace App\Controller;

use App\Entity\User;
use App\Factory\JsonResponseFactory;
use App\Repository\UserRepository;
use App\Services\ApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\Security\Http\Attribute\CurrentUser;
#[Route('/api/v1/user')]
class UserController extends AbstractController
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
                'body' => ['firstName', 'lastName', 'email', 'password', 'username', 'city', 'postalCode', 'userType', 'termsAccepted'],
            ],
            'get' => [
                'query' => [],
                'body' => []
            ],
            'edit' => [
                'query' => [],
                'body' => ['firstName', 'lastName', 'email', 'username', 'city', 'postalCode', 'userType', 'termsAccepted'],
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
                'body' => ['firstName', 'lastName', 'email', 'password', 'city', 'postalCode', 'userType', 'termsAccepted'],
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

    #[Route('/', name: 'app_user_index', methods: ['GET'])]
    public function index(#[MapQueryParameter] int $page, #[MapQueryParameter] int $limit, UserRepository $userRepository, Request $request): JsonResponse
    {

        $requestValidation = $this->apiService->hasValidBodyAndQueryParameters(
            $request,
            $this->allowedProperties['find']['body'],
            $this->requiredFields['find']['body'],
            $this->allowedProperties['find']['query'],
            $this->requiredFields['find']['query'],
        );


        if (!$requestValidation['yes']) {
            return $this->jsonResponseFactory->create(
                (object) [
                    'error' => true,
                    'message' => Response::$statusTexts[Response::HTTP_BAD_REQUEST],
                    'description' => 'The request has invalid query parameters or body fields.',
                    'code' => Response::HTTP_BAD_REQUEST,
                    'body' => $requestValidation['body'],
                    'params' => $requestValidation['params']
                ],
                Response::HTTP_BAD_REQUEST,
            );
        }


        try {
            $users = $userRepository->findBy([], [], $limit, ($page - 1) * $limit);
            return $this->jsonResponseFactory->create(
                (object) [
                    'error' => false,
                    'message' => Response::$statusTexts[Response::HTTP_OK],
                    'code' => Response::HTTP_OK,
                    'datas' => $users,
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $userRepository->count([]),
                    'totalPages' => (int) ceil($userRepository->count([]) / $limit)
                ],
                Response::HTTP_OK,
            );
        } catch (\Throwable $th) {
            return $this->jsonResponseFactory->create(
                (object) [
                    'error' => true,
                    'message' => Response::$statusTexts[Response::HTTP_INTERNAL_SERVER_ERROR],
                    'description' => $th->getMessage(),
                    'code' => Response::HTTP_INTERNAL_SERVER_ERROR
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    #[Route('/current', name: 'app_user_current', methods: ['GET'])]
    public function currentUser(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->jsonResponseFactory->create(
                (object) [
                    'error' => true,
                    'message' => Response::$statusTexts[Response::HTTP_UNAUTHORIZED],
                    'description' => 'User not authenticated.',
                    'code' => Response::HTTP_UNAUTHORIZED,
                ],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        return $this->jsonResponseFactory->create(
            (object) [
                'error' => false,
                'message' => Response::$statusTexts[Response::HTTP_OK],
                'code' => Response::HTTP_OK,
                'datas' => $user,
            ],
            Response::HTTP_OK,
        );
    }

    #[Route('/me', name: 'user_me', methods: ['GET'])]
    public function getUserDetails(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'User not authenticated.'], 401);
        }

        return $this->json([
            'id' => $user->getId(),
            'user_type' => $user->getUserType(),
        ]);
    }
    #[Route('/create', name: 'app_user_new', methods: ['POST'])]
    public function create(Request $request, UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $requestValidation = $this->apiService->hasValidBodyAndQueryParameters(
            $request,
            $this->allowedProperties['create']['body'],
            $this->requiredFields['create']['body'],
            $this->allowedProperties['create']['query'],
            $this->requiredFields['create']['query'],
        );

        if (!$requestValidation['yes']) {
            return $this->jsonResponseFactory->create(
                (object) [
                    'error' => true,
                    'ok' => false,
                    'message' => Response::$statusTexts[Response::HTTP_BAD_REQUEST],
                    'description' => 'The request has invalid query parameters or body fields.',
                    'code' => Response::HTTP_BAD_REQUEST,
                    'body' => $requestValidation['body'],
                    'params' => $requestValidation['params']
                ],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $bodyData = json_decode($request->getContent(), true);

        $email = $bodyData['email'] ?? null;
        $is_email_unique = $userRepository->findUserByEmail($email) === null;
        if (!$is_email_unique) {
            return $this->jsonResponseFactory->create(
                (object) [
                    'error' => true,
                    'ok' => false,
                    'message' => Response::$statusTexts[Response::HTTP_BAD_REQUEST],
                    'description' => 'Not unique email!!!',
                    'code' => Response::HTTP_BAD_REQUEST,
                    'body' => $requestValidation['body'],
                    'params' => $requestValidation['params']
                ],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $user = new User();
        $user->setFirstName($bodyData['firstName'] ?? null);
        $user->setLastName($bodyData['lastName'] ?? null);
        $user->setEmail($email);
        $user->setUsername($bodyData['username'] ?? null);
        $user->setCity($bodyData['city'] ?? null);
        $user->setPostalCode($bodyData['postalCode'] ?? null);
        $user->setUserType($bodyData['userType'] ?? null);
        $user->setTermsAccepted($bodyData['termsAccepted'] ?? false);
        $user->setPassword($passwordHasher->hashPassword($user, $bodyData['password'] ?? null));


        try {
            $userRepository->save($user, true);
            return $this->jsonResponseFactory->create(
                (object) [
                    'error' => false,
                    'ok' => true,
                    'message' => Response::$statusTexts[Response::HTTP_CREATED],
                    'description' => 'The resource has been created.',
                    'code' => Response::HTTP_CREATED,
                    'datas' => $user
                ],
                Response::HTTP_CREATED,
            );
        } catch (\Throwable $th) {
            return $this->jsonResponseFactory->create(
                (object) [
                    'error' => true,
                    'ok' => false,
                    'message' => Response::$statusTexts[Response::HTTP_INTERNAL_SERVER_ERROR],
                    'description' => $th->getMessage(),
                    'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'datas' => $user
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    #[Route('/get/{id}', name: 'app_user_show', methods: ['GET'])]
    public function get(User $user = null, Request $request): JsonResponse
    {


        if (!$user) {
            return $this->jsonResponseFactory->create(
                (object) [
                    'error' => true,
                    'message' => Response::$statusTexts[Response::HTTP_NOT_FOUND],
                    'description' => 'The requested resource was not found.',
                    'code' => Response::HTTP_NOT_FOUND,
                ],
                Response::HTTP_NOT_FOUND,
            );
        }

        $requestValidation = $this->apiService->hasValidBodyAndQueryParameters(
            $request,
            $this->allowedProperties['get']['body'],
            $this->requiredFields['get']['body'],
            $this->allowedProperties['get']['query'],
            $this->requiredFields['get']['query'],
        );

        if (!$requestValidation['yes']) {
            return $this->jsonResponseFactory->create(
                (object) [
                    'error' => true,
                    'message' => Response::$statusTexts[Response::HTTP_BAD_REQUEST],
                    'description' => 'The request has invalid query parameters or body fields.',
                    'code' => Response::HTTP_BAD_REQUEST,
                    'body' => $requestValidation['body'],
                    'params' => $requestValidation['params']
                ],
                Response::HTTP_BAD_REQUEST,
            );
        }



        return $this->jsonResponseFactory->create(
            (object) [
                'error' => false,
                'message' => Response::$statusTexts[Response::HTTP_OK],
                'code' => Response::HTTP_OK,
                'datas' => $user
            ],
            Response::HTTP_OK,
        );
    }

    #[Route('/edit/{id}', name: 'app_user_edit', methods: ['PUT', 'PATCH'])]
    public function edit(Request $request, User $user = null, UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        if (!$user) {
            return $this->jsonResponseFactory->create(
                (object) [
                    'error' => true,
                    'message' => Response::$statusTexts[Response::HTTP_NOT_FOUND],
                    'description' => 'The requested resource was not found.',
                    'code' => Response::HTTP_NOT_FOUND,
                ],
                Response::HTTP_NOT_FOUND,
            );
        }

        $requestValidation = $this->apiService->hasValidBodyAndQueryParameters(
            $request,
            $this->allowedProperties['edit']['body'],
            $this->requiredFields['edit']['body'],
            $this->allowedProperties['edit']['query'],
            $this->requiredFields['edit']['query'],
            false
        );

        if (!$requestValidation['yes']) {
            return $this->jsonResponseFactory->create(
                (object) [
                    'error' => true,
                    'message' => Response::$statusTexts[Response::HTTP_BAD_REQUEST],
                    'description' => 'The request has invalid query parameters or body fields.',
                    'code' => Response::HTTP_BAD_REQUEST,
                    'body' => $requestValidation['body'],
                    'params' => $requestValidation['params']
                ],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $bodyData = json_decode($request->getContent(), true);

        // Ensure at least one field has been provided
        if (empty($bodyData)) {
            return $this->jsonResponseFactory->create(
                (object) [
                    'error' => true,
                    'message' => Response::$statusTexts[Response::HTTP_BAD_REQUEST],
                    'description' => 'You must provided at least one field to update.',
                    'code' => Response::HTTP_BAD_REQUEST
                ],
                Response::HTTP_BAD_REQUEST,
            );
        }

        // Only update the fields that have been changed (not null and present in the request)
        foreach ($bodyData as $key => $value) {
            if ($value !== null) {
                $setter = 'set' . ucfirst($key);
                if (method_exists($user, $setter)) {
                    if ($key === 'password') {
                        $user->setPassword($passwordHasher->hashPassword($user, $value));
                    } else {
                        $user->$setter($value);
                    }
                }
            }
        }

        try {
            $userRepository->save($user, true);
            return $this->jsonResponseFactory->create(
                (object) [
                    'error' => false,
                    'message' => Response::$statusTexts[Response::HTTP_OK],
                    'description' => 'The resource has been updated.',
                    'code' => Response::HTTP_OK,
                    'datas' => $user
                ],
                Response::HTTP_OK,
            );
        } catch (\Throwable $th) {
            return $this->jsonResponseFactory->create(
                (object) [
                    'error' => true,
                    'message' => Response::$statusTexts[Response::HTTP_INTERNAL_SERVER_ERROR],
                    'description' => 'An error occured while updating the resource.',
                    'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }
    #[Route('/edit/passwd', name: 'app_user_password', methods: ['POST'])]
    public function editPassword(Request $request, UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->jsonResponseFactory->create(
                (object) [
                    'error' => true,
                    'message' => Response::$statusTexts[Response::HTTP_UNAUTHORIZED],
                    'description' => 'User not authenticated.',
                    'code' => Response::HTTP_UNAUTHORIZED,
                ],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        $bodyData = json_decode($request->getContent(), true);
        $currentPassword = $bodyData['currentPassword'] ?? null;
        $newPassword = $bodyData['newPassword'] ?? null;

        if (!$currentPassword || !$newPassword) {
            return $this->jsonResponseFactory->create(
                (object) [
                    'error' => true,
                    'message' => Response::$statusTexts[Response::HTTP_BAD_REQUEST],
                    'description' => 'Current and new password are required.',
                    'code' => Response::HTTP_BAD_REQUEST,
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Verify the current password
        if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
            return $this->jsonResponseFactory->create(
                (object) [
                    'error' => true,
                    'message' => Response::$statusTexts[Response::HTTP_UNAUTHORIZED],
                    'description' => 'Current password is incorrect.',
                    'code' => Response::HTTP_UNAUTHORIZED,
                ],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        // Hash and update the password
        $user->setPassword($passwordHasher->hashPassword($user, $newPassword));

        try {
            $userRepository->save($user, true);
            return $this->jsonResponseFactory->create(
                (object) [
                    'error' => false,
                    'message' => 'Password updated successfully.',
                    'code' => Response::HTTP_OK,
                ],
                Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            return $this->jsonResponseFactory->create(
                (object) [
                    'error' => true,
                    'message' => Response::$statusTexts[Response::HTTP_INTERNAL_SERVER_ERROR],
                    'description' => 'An error occurred while updating the password.',
                    'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/delete/{id}', name: 'app_user_delete', methods: ['DELETE'])]
    public function delete(Request $request, User $user = null, UserRepository $userRepository): JsonResponse
    {

        // Ensure the user exists in the database
        if (!$user) {
            return $this->jsonResponseFactory->create(
                (object) [
                    'error' => true,
                    'message' => Response::$statusTexts[Response::HTTP_NOT_FOUND],
                    'description' => 'The requested resource was not found.',
                    'code' => Response::HTTP_NOT_FOUND,
                ],
                Response::HTTP_NOT_FOUND,
            );
        }

        $requestValidation = $this->apiService->hasValidBodyAndQueryParameters(
            $request,
            $this->allowedProperties['delete']['body'],
            $this->requiredFields['delete']['body'],
            $this->allowedProperties['delete']['query'],
            $this->requiredFields['delete']['query'],
        );

        if (!$requestValidation['yes']) {
            return $this->jsonResponseFactory->create(
                (object) [
                    'error' => true,
                    'message' => Response::$statusTexts[Response::HTTP_BAD_REQUEST],
                    'description' => 'The request has invalid query parameters or body fields.',
                    'code' => Response::HTTP_BAD_REQUEST,
                    'body' => $requestValidation['body'],
                    'params' => $requestValidation['params']
                ],
                Response::HTTP_BAD_REQUEST,
            );
        }


        try {
            $userRepository->remove($user, true);
            return $this->jsonResponseFactory->create(
                (object) [
                    'error' => false,
                    'message' => Response::$statusTexts[Response::HTTP_OK],
                    'description' => 'The resource has been deleted.',
                    'code' => Response::HTTP_OK,
                ],
                Response::HTTP_OK,
            );
        } catch (\Throwable $th) {
            return $this->jsonResponseFactory->create(
                (object) [
                    'error' => true,
                    'message' => Response::$statusTexts[Response::HTTP_INTERNAL_SERVER_ERROR],
                    'description' => 'An error occured while deleting the resource.',
                    'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }
}
