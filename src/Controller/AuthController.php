<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/api", name="api_auth_")
 */
class AuthController extends AbstractController
{
    private $userRepository;
    private $passwordHasher;
    private $validator;
    private $logger;

    public function __construct(
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator,
        LoggerInterface $logger
    ) {
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
        $this->validator = $validator;
        $this->logger = $logger;
    }

    /**
     * @Route("/register", name="register", methods={"POST"})
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return $this->json([
                    'success' => false,
                    'error' => 'Invalid JSON data',
                ], Response::HTTP_BAD_REQUEST);
            }

            $existingUser = $this->userRepository->findOneBy(['email' => $data['email'] ?? '']);
            if ($existingUser) {
                return $this->json([
                    'success' => false,
                    'error' => 'User with this email already exists',
                ], Response::HTTP_BAD_REQUEST);
            }

            $user = new User();
            $user->setEmail($data['email'] ?? '');
            $user->setName($data['name'] ?? '');

            $errors = $this->validator->validate($user);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[$error->getPropertyPath()] = $error->getMessage();
                }

                return $this->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'details' => $errorMessages,
                ], Response::HTTP_BAD_REQUEST);
            }

            if (empty($data['password'])) {
                return $this->json([
                    'success' => false,
                    'error' => 'Password is required',
                ], Response::HTTP_BAD_REQUEST);
            }

            if (strlen($data['password']) < 6) {
                return $this->json([
                    'success' => false,
                    'error' => 'Password must be at least 6 characters long',
                ], Response::HTTP_BAD_REQUEST);
            }

            $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);

            $this->userRepository->save($user);

            $this->logger->info('User registered successfully', [
                'userId' => $user->getId(),
                'email' => $user->getEmail(),
            ]);

            return $this->json([
                'success' => true,
                'message' => 'User registered successfully',
                'data' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'name' => $user->getName(),
                ],
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            $this->logger->error('Error registering user: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return $this->json([
                'success' => false,
                'error' => 'An error occurred while registering the user',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

