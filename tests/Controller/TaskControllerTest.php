<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class TaskControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testCreateTask(): void
    {
        $this->client->request(
            'POST',
            '/api/tasks',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'title' => 'Test Task',
                'description' => 'This is a test task',
                'status' => 'pending'
            ])
        );

        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals('Test Task', $responseData['data']['title']);
        $this->assertEquals('This is a test task', $responseData['data']['description']);
        $this->assertEquals('pending', $responseData['data']['status']);
    }

    public function testCreateTaskWithEmptyTitle(): void
    {
        $this->client->request(
            'POST',
            '/api/tasks',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'title' => '',
                'description' => 'This is a test task',
                'status' => 'pending'
            ])
        );

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Validation failed', $responseData['error']);
    }

    public function testCreateTaskWithInvalidStatus(): void
    {
        $this->client->request(
            'POST',
            '/api/tasks',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'title' => 'Test Task',
                'description' => 'This is a test task',
                'status' => 'invalid_status'
            ])
        );

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($responseData['success']);
    }

    public function testGetAllTasks(): void
    {
        $this->client->request(
            'POST',
            '/api/tasks',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'title' => 'Test Task for List',
                'description' => 'Test description',
                'status' => 'pending'
            ])
        );

        $this->client->request('GET', '/api/tasks');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertIsArray($responseData['data']);
        $this->assertArrayHasKey('pagination', $responseData);
    }

    public function testGetTaskById(): void
    {
        $this->client->request(
            'POST',
            '/api/tasks',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'title' => 'Test Task for Get',
                'description' => 'Test description',
                'status' => 'pending'
            ])
        );

        $createResponse = json_decode($this->client->getResponse()->getContent(), true);
        $taskId = $createResponse['data']['id'];

        $this->client->request('GET', '/api/tasks/' . $taskId);

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals($taskId, $responseData['data']['id']);
        $this->assertEquals('Test Task for Get', $responseData['data']['title']);
    }

    public function testGetNonExistentTask(): void
    {
        $this->client->request('GET', '/api/tasks/999999');

        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Task not found', $responseData['error']);
    }

    public function testUpdateTask(): void
    {
        $this->client->request(
            'POST',
            '/api/tasks',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'title' => 'Original Title',
                'description' => 'Original description',
                'status' => 'pending'
            ])
        );

        $createResponse = json_decode($this->client->getResponse()->getContent(), true);
        $taskId = $createResponse['data']['id'];

        $this->client->request(
            'PUT',
            '/api/tasks/' . $taskId,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'title' => 'Updated Title',
                'status' => 'completed'
            ])
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals('Updated Title', $responseData['data']['title']);
        $this->assertEquals('completed', $responseData['data']['status']);
    }

    public function testDeleteTask(): void
    {
        $this->client->request(
            'POST',
            '/api/tasks',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'title' => 'Task to Delete',
                'description' => 'This task will be deleted',
                'status' => 'pending'
            ])
        );

        $createResponse = json_decode($this->client->getResponse()->getContent(), true);
        $taskId = $createResponse['data']['id'];

        $this->client->request('DELETE', '/api/tasks/' . $taskId);

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['success']);

        $this->client->request('GET', '/api/tasks/' . $taskId);
        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testPagination(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            $this->client->request(
                'POST',
                '/api/tasks',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'title' => "Task $i",
                    'description' => "Description $i",
                    'status' => 'pending'
                ])
            );
        }

        $this->client->request('GET', '/api/tasks?page=1&limit=5');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertCount(5, $responseData['data']);
        $this->assertEquals(1, $responseData['pagination']['page']);
        $this->assertEquals(5, $responseData['pagination']['limit']);
        $this->assertGreaterThanOrEqual(15, $responseData['pagination']['total']);
    }

    public function testFilterByStatus(): void
    {
        $this->client->request(
            'POST',
            '/api/tasks',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'title' => 'Completed Task',
                'description' => 'This is completed',
                'status' => 'completed'
            ])
        );

        $this->client->request(
            'POST',
            '/api/tasks',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'title' => 'Pending Task',
                'description' => 'This is pending',
                'status' => 'pending'
            ])
        );

        $this->client->request('GET', '/api/tasks?status=completed');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['success']);
        
        foreach ($responseData['data'] as $task) {
            $this->assertEquals('completed', $task['status']);
        }
    }
}

