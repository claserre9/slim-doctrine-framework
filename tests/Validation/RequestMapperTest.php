<?php

namespace Tests\Validation;

use App\Validation\RequestMapper;
use App\Validation\ValidationException;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

final class RequestMapperTest extends TestCase
{
    private RequestMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new RequestMapper(
            Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator()
        );
    }

    /**
     * @param array<string, mixed>      $query
     * @param array<string, mixed>|null $body
     */
    private function map(array $query, ?array $body = null): SampleRequest
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/')
            ->withQueryParams($query)
            ->withParsedBody($body);

        return $this->mapper->map($request, SampleRequest::class);
    }

    /**
     * @param array<string, mixed>      $query
     * @param array<string, mixed>|null $body
     *
     * @return array<string, list<string>>
     */
    private function errorsFor(array $query, ?array $body = null): array
    {
        try {
            $this->map($query, $body);
        } catch (ValidationException $e) {
            return $e->getErrors();
        }

        $this->fail('A ValidationException was expected');
    }

    public function testCastsQueryStringsToDeclaredTypes(): void
    {
        $dto = $this->map(['email' => 'ada@example.com', 'age' => '36', 'newsletter' => 'true', 'score' => '4.5']);

        $this->assertSame('ada@example.com', $dto->email);
        $this->assertSame(36, $dto->age);
        $this->assertTrue($dto->newsletter);
        $this->assertSame(4.5, $dto->score);
    }

    public function testParsedBodyOverridesQueryParams(): void
    {
        $dto = $this->map(['email' => 'query@example.com'], ['email' => 'body@example.com', 'age' => 36]);

        $this->assertSame('body@example.com', $dto->email);
        $this->assertSame(36, $dto->age);
    }

    public function testAppliesDefaultsAndNullForMissingOptionalFields(): void
    {
        $dto = $this->map(['email' => 'ada@example.com']);

        $this->assertNull($dto->age);
        $this->assertFalse($dto->newsletter);
        $this->assertNull($dto->score);
    }

    public function testUnknownFieldsDoNotCauseErrors(): void
    {
        $dto = $this->map(['email' => 'ada@example.com', 'isAdmin' => '1']);

        $this->assertSame('ada@example.com', $dto->email);
    }

    public function testReportsMissingRequiredFields(): void
    {
        $this->assertSame(['email' => ['This value is required.']], $this->errorsFor([]));
    }

    public function testReportsTypeErrorsPerField(): void
    {
        $errors = $this->errorsFor(['email' => ['not', 'a', 'string'], 'age' => 'abc', 'newsletter' => 'maybe']);

        $this->assertSame([
            'email' => ['This value should be of type string.'],
            'age' => ['This value should be of type int.'],
            'newsletter' => ['This value should be of type bool.'],
        ], $errors);
    }

    public function testRejectsNullForNonNullableFields(): void
    {
        $this->assertSame(['email' => ['This value should not be null.']], $this->errorsFor([], ['email' => null]));
    }

    public function testReportsConstraintViolations(): void
    {
        $errors = $this->errorsFor(['email' => 'not-an-email', 'age' => '-1']);

        $this->assertSame(['This value is not a valid email address.'], $errors['email']);
        $this->assertSame(['This value should be positive.'], $errors['age']);
    }
}

final class SampleRequest
{
    public function __construct(
        #[Assert\Email]
        public readonly string $email,
        #[Assert\Positive]
        public readonly ?int $age,
        public readonly bool $newsletter = false,
        public readonly ?float $score = null,
    ) {
    }
}
