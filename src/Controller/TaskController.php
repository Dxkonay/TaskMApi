<?php

namespace App\Controller;

use App\Entity\Task;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/api/tasks", name="api_tasks_")
 */
class TaskController extends AbstractController
{
    private $taskRepository;
    private $entityManager;
    private $validator;
    private $logger;

    public function __construct(
        TaskRepository $taskRepository,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        LoggerInterface $logger
    ) {
        $this->taskRepository = $taskRepository;
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->logger = $logger;
    }

    /**
     * @Route("", name="list", methods={"GET"})
     */
    public function list(Request $request): JsonResponse
    {
        try {
            $page = max(1, (int) $request->query->get('page', 1));
            $limit = max(1, min(100, (int) $request->query->get('limit', 10)));
            $status = $request->query->get('status');

            if ($status && !in_array($status, ['pending', 'in_progress', 'completed'])) {
                return $this->json([
                    'error' => 'Invalid status. Must be one of: pending, in_progress, completed'
                ], Response::HTTP_BAD_REQUEST);
            }

            $result = $this->taskRepository->findPaginated($page, $limit, $status);

            return $this->json([
                'success' => true,
                'data' => array_map(fn(Task $task) => $task->toArray(), $result['items']),
                'pagination' => [
                    'total' => $result['total'],
                    'page' => $result['page'],
                    'limit' => $result['limit'],
                    'pages' => $result['pages'],
                ],
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error fetching tasks: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->json([
                'success' => false,
                'error' => 'An error occurred while fetching tasks',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @Route("/{id}", name="show", methods={"GET"}, requirements={"id"="\d+"})
     */
    public function show(int $id): JsonResponse
    {
        try {
            $task = $this->taskRepository->find($id);

            if (!$task) {
                return $this->json([
                    'success' => false,
                    'error' => 'Task not found',
                ], Response::HTTP_NOT_FOUND);
            }

            return $this->json([
                'success' => true,
                'data' => $task->toArray(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error fetching task: ' . $e->getMessage(), [
                'taskId' => $id,
                'exception' => $e,
            ]);

            return $this->json([
                'success' => false,
                'error' => 'An error occurred while fetching the task',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @Route("", name="create", methods={"POST"})
     */
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return $this->json([
                    'success' => false,
                    'error' => 'Invalid JSON data',
                ], Response::HTTP_BAD_REQUEST);
            }

            $task = new Task();
            $task->setTitle($data['title'] ?? '');
            $task->setDescription($data['description'] ?? null);
            $task->setStatus($data['status'] ?? 'pending');

            $user = $this->getUser();
            if ($user) {
                $task->setUser($user);
            }

            $errors = $this->validator->validate($task);
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

            $this->taskRepository->save($task);

            $this->logger->info('Task created successfully', [
                'taskId' => $task->getId(),
                'title' => $task->getTitle(),
            ]);

            return $this->json([
                'success' => true,
                'message' => 'Task created successfully',
                'data' => $task->toArray(),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            $this->logger->error('Error creating task: ' . $e->getMessage(), [
                'exception' => $e,
                'data' => $data ?? null,
            ]);

            return $this->json([
                'success' => false,
                'error' => 'An error occurred while creating the task',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @Route("/{id}", name="update", methods={"PUT"}, requirements={"id"="\d+"})
     */
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $task = $this->taskRepository->find($id);

            if (!$task) {
                return $this->json([
                    'success' => false,
                    'error' => 'Task not found',
                ], Response::HTTP_NOT_FOUND);
            }

            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return $this->json([
                    'success' => false,
                    'error' => 'Invalid JSON data',
                ], Response::HTTP_BAD_REQUEST);
            }

            if (isset($data['title'])) {
                $task->setTitle($data['title']);
            }
            if (isset($data['description'])) {
                $task->setDescription($data['description']);
            }
            if (isset($data['status'])) {
                $task->setStatus($data['status']);
            }

            $errors = $this->validator->validate($task);
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

            $this->entityManager->flush();

            $this->logger->info('Task updated successfully', [
                'taskId' => $task->getId(),
                'title' => $task->getTitle(),
            ]);

            return $this->json([
                'success' => true,
                'message' => 'Task updated successfully',
                'data' => $task->toArray(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error updating task: ' . $e->getMessage(), [
                'taskId' => $id,
                'exception' => $e,
            ]);

            return $this->json([
                'success' => false,
                'error' => 'An error occurred while updating the task',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @Route("/{id}", name="delete", methods={"DELETE"}, requirements={"id"="\d+"})
     */
    public function delete(int $id): JsonResponse
    {
        try {
            $task = $this->taskRepository->find($id);

            if (!$task) {
                return $this->json([
                    'success' => false,
                    'error' => 'Task not found',
                ], Response::HTTP_NOT_FOUND);
            }

            $this->taskRepository->remove($task);

            $this->logger->info('Task deleted successfully', [
                'taskId' => $id,
            ]);

            return $this->json([
                'success' => true,
                'message' => 'Task deleted successfully',
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error deleting task: ' . $e->getMessage(), [
                'taskId' => $id,
                'exception' => $e,
            ]);

            return $this->json([
                'success' => false,
                'error' => 'An error occurred while deleting the task',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

