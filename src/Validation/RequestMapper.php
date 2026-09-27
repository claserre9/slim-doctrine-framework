<?php

namespace App\Validation;

use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Builds a request DTO from the query params and parsed body, then validates it
 * against its constraint attributes (symfony/validator).
 *
 * Name matches input to the DTO constructor parameters; scalar values are
 * cast to the declared type (query strings are always strings), and unknown keys
 * are ignored. Any error throws a ValidationException (HTTP 422).
 */
final class RequestMapper
{
    public function __construct(
        private readonly ValidatorInterface $validator,
    ) {
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @throws ValidationException|ReflectionException
     *
     * @return T
     */
    public function map(ServerRequestInterface $request, string $class): object
    {
        $data = $this->getInputData($request);
        $args = [];
        $errors = [];

        foreach ((new ReflectionClass($class))->getConstructor()?->getParameters() ?? [] as $parameter) {
            $name = $parameter->getName();

            if (!array_key_exists($name, $data)) {
                if ($parameter->isDefaultValueAvailable()) {
                    $args[$name] = $parameter->getDefaultValue();
                } elseif ($parameter->allowsNull()) {
                    $args[$name] = null;
                } else {
                    $errors[$name][] = 'This value is required.';
                }

                continue;
            }

            try {
                $args[$name] = $this->cast($data[$name], $parameter);
            } catch (\InvalidArgumentException $e) {
                $errors[$name][] = $e->getMessage();
            }
        }

        if ($errors !== []) {
            throw new ValidationException($request, $errors);
        }

        $dto = new $class(...$args);

        foreach ($this->validator->validate($dto) as $violation) {
            $errors[$violation->getPropertyPath()][] = (string) $violation->getMessage();
        }

        if ($errors !== []) {
            throw new ValidationException($request, $errors);
        }

        return $dto;
    }

    /**
     * @throws \InvalidArgumentException when the value cannot be cast to the parameter type
     */
    private function cast(mixed $value, ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        // Untyped, union or class-typed parameters receive the raw value
        if (!$type instanceof ReflectionNamedType || !$type->isBuiltin()) {
            return $value;
        }

        if ($value === null) {
            return $type->allowsNull() ? null : throw new \InvalidArgumentException('This value should not be null.');
        }

        $cast = match ($type->getName()) {
            'string' => is_string($value) || is_int($value) || is_float($value) ? (string) $value : null,
            'int' => is_bool($value) ? null : filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE),
            'float' => is_bool($value) ? null : filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE),
            'bool' => is_bool($value) ? $value : filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE),
            'array' => is_array($value) ? $value : null,
            default => $value,
        };

        if ($cast === null) {
            throw new \InvalidArgumentException(sprintf('This value should be of type %s.', $type->getName()));
        }

        return $cast;
    }

    /**
     * @return array<string, mixed>
     */
    private function getInputData(ServerRequestInterface $request): array
    {
        $parsed = $request->getParsedBody();

        return is_array($parsed)
            ? array_merge($request->getQueryParams(), $parsed)
            : $request->getQueryParams();
    }
}
