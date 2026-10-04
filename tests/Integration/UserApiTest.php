<?php

namespace Tests\Integration;

use Tests\Support\ApiTestCase;

final class UserApiTest extends ApiTestCase
{
    public function testCreateUser(): void
    {
        $response = $this->request('POST', '/users', [
            'email' => ' Ada@Example.com',
            'name' => 'Ada Lovelace',
            'password' => 'correct horse',
        ]);
        $user = $this->json($response);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('/users/' . $user['id'], $response->getHeaderLine('Location'));
        $this->assertSame('ada@example.com', $user['email']);
        $this->assertSame('Ada Lovelace', $user['name']);
        $this->assertSame(['id', 'email', 'name', 'created_at', 'updated_at'], array_keys($user));
    }

    public function testCreateUserValidatesInput(): void
    {
        $response = $this->request('POST', '/users', ['email' => 'not-an-email', 'name' => ' ', 'password' => 'short']);
        $errors = $this->json($response)['error']['errors'];

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame(['email', 'name', 'password'], array_keys($errors));
    }

    public function testEmailMustBeUniqueIgnoringCase(): void
    {
        $this->createUser('ada@example.com');

        $response = $this->request('POST', '/users', ['email' => 'ADA@example.com', 'name' => 'Other', 'password' => 'correct horse']);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame(['email' => ['This email address is already used.']], $this->json($response)['error']['errors']);
    }

    public function testListUsersIsPaginated(): void
    {
        foreach (range(1, 3) as $i) {
            $this->createUser("user$i@example.com", "User $i");
        }
        $token = $this->login('user1@example.com');

        $body = $this->json($this->request('GET', '/users?page=2&limit=2', token: $token));

        $this->assertSame(['page' => 2, 'limit' => 2, 'total' => 3], $body['meta']);
        $this->assertSame(['user3@example.com'], array_column($body['data'], 'email'));
    }

    public function testListUsersValidatesPagination(): void
    {
        $this->createUser();

        $response = $this->request('GET', '/users?limit=1000', token: $this->login());

        $this->assertSame(422, $response->getStatusCode());
    }

    public function testShowUser(): void
    {
        $user = $this->createUser();
        $token = $this->login();

        $this->assertSame($user, $this->json($this->request('GET', '/users/' . $user['id'], token: $token)));
        $this->assertSame(404, $this->request('GET', '/users/999', token: $token)->getStatusCode());
    }

    public function testUsersCanUpdateTheirOwnAccount(): void
    {
        $user = $this->createUser();

        $response = $this->request('PATCH', '/users/' . $user['id'], ['name' => 'Countess Lovelace'], $this->login());
        $updated = $this->json($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Countess Lovelace', $updated['name']);
        $this->assertSame($user['email'], $updated['email']);
    }

    public function testUsersCannotModifyOtherAccounts(): void
    {
        $ada = $this->createUser('ada@example.com');
        $this->createUser('grace@example.com', 'Grace Hopper');
        $graceToken = $this->login('grace@example.com');

        $this->assertSame(403, $this->request('PATCH', '/users/' . $ada['id'], ['name' => 'Hacked'], $graceToken)->getStatusCode());
        $this->assertSame(403, $this->request('DELETE', '/users/' . $ada['id'], token: $graceToken)->getStatusCode());
    }

    public function testChangingEmailToATakenOneIsRejected(): void
    {
        $ada = $this->createUser('ada@example.com');
        $this->createUser('grace@example.com', 'Grace Hopper');

        $response = $this->request('PATCH', '/users/' . $ada['id'], ['email' => 'grace@example.com'], $this->login());

        $this->assertSame(422, $response->getStatusCode());
    }

    public function testChangingPasswordRevokesOtherSessions(): void
    {
        $user = $this->createUser();
        $current = $this->login();
        $other = $this->login();

        $response = $this->request('PATCH', '/users/' . $user['id'], ['password' => 'new password'], $current);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(200, $this->request('GET', '/auth/me', token: $current)->getStatusCode());
        $this->assertSame(401, $this->request('GET', '/auth/me', token: $other)->getStatusCode());
        $this->assertSame(401, $this->request('POST', '/auth/login', ['email' => 'ada@example.com', 'password' => 'correct horse'])->getStatusCode());
        $this->login(password: 'new password');
    }

    public function testDeleteOwnAccount(): void
    {
        $user = $this->createUser();
        $token = $this->login();

        $this->assertSame(204, $this->request('DELETE', '/users/' . $user['id'], token: $token)->getStatusCode());
        $this->assertSame(401, $this->request('GET', '/auth/me', token: $token)->getStatusCode());
        $this->assertSame(401, $this->request('POST', '/auth/login', ['email' => 'ada@example.com', 'password' => 'correct horse'])->getStatusCode());
    }
}
