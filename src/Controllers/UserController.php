<?php

namespace App\Controllers;

use App\Entities\User;
use App\Middleware\AuthMiddleware;
use App\Repositories\UserRepository;
use App\Requests\CreateUserRequest;
use App\Requests\ListUsersRequest;
use App\Requests\UpdateUserRequest;
use App\Responses\UserResource;
use App\Services\EmailAlreadyUsedException;
use App\Services\UserService;
use App\Validation\RequestMapper;
use App\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;

final class UserController
{
    use RespondsWithJson;

    public function __construct(
        private readonly RequestMapper $mapper,
        private readonly UserRepository $users,
        private readonly UserService $userService,
    ) {
    }

    public function list(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $input = $this->mapper->map($request, ListUsersRequest::class);
        $result = $this->users->paginate($input->page, $input->limit);

        return $this->json($response, [
            'data' => array_map(UserResource::toArray(...), $result['items']),
            'meta' => ['page' => $input->page, 'limit' => $input->limit, 'total' => $result['total']],
        ]);
    }

    /**
     * @param array{id: string} $args
     */
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->json($response, UserResource::toArray($this->findUser($request, $args)));
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $input = $this->mapper->map($request, CreateUserRequest::class);

        try {
            $user = $this->userService->create($input->email, $input->name, $input->password);
        } catch (EmailAlreadyUsedException) {
            throw new ValidationException($request, ['email' => ['This email address is already used.']]);
        }

        return $this->json($response, UserResource::toArray($user), 201)
            ->withHeader('Location', '/users/' . $user->getId());
    }

    /**
     * @param array{id: string} $args
     */
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $user = $this->findOwnUser($request, $args);
        $input = $this->mapper->map($request, UpdateUserRequest::class);

        try {
            $this->userService->update(
                $user,
                $input->email,
                $input->name,
                $input->password,
                AuthMiddleware::token($request),
            );
        } catch (EmailAlreadyUsedException) {
            throw new ValidationException($request, ['email' => ['This email address is already used.']]);
        }

        return $this->json($response, UserResource::toArray($user));
    }

    /**
     * @param array{id: string} $args
     */
    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->userService->delete($this->findOwnUser($request, $args));

        return $response->withStatus(204);
    }

    /**
     * @param array{id: string} $args
     */
    private function findUser(ServerRequestInterface $request, array $args): User
    {
        return $this->users->find((int) $args['id'])
            ?? throw new HttpNotFoundException($request, 'User not found.');
    }

    /**
     * Users can only modify their own account.
     *
     * @param array{id: string} $args
     */
    private function findOwnUser(ServerRequestInterface $request, array $args): User
    {
        $user = $this->findUser($request, $args);

        if ($user !== AuthMiddleware::user($request)) {
            throw new HttpForbiddenException($request, 'You can only modify your own account.');
        }

        return $user;
    }
}
