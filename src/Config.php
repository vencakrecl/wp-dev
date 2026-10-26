<?php

declare(strict_types=1);

namespace WPDev;

final readonly class Config
{
    public function __construct(
        private ?string $dbName = null,
        private ?string $dbUser = null,
        #[\SensitiveParameter] private ?string $dbPassword = null,
        private ?string $dbHost = null,
        private ?string $dbPrefix = null,
        private ?string $prodHost = null,
        private ?string $devHost = null,
    ) {
    }

    public static function fromEnvironment(): self
    {
        return new self(
            dbName: self::read('DB_NAME'),
            dbUser: self::read('DB_USER'),
            dbPassword: self::read('DB_PASS'),
            dbHost: self::read('DB_HOST'),
            dbPrefix: self::read('DB_PREFIX'),
            prodHost: self::read('PROD_HOST'),
            devHost: self::read('DEV_HOST'),
        );
    }

    public function databaseName(): string
    {
        return self::required($this->dbName, 'DB_NAME');
    }

    public function databaseUser(): string
    {
        return self::required($this->dbUser, 'DB_USER');
    }

    public function databasePassword(): string
    {
        $password = self::required($this->dbPassword, 'DB_PASS', allowEmpty: true);
        if (str_contains($password, "\n") || str_contains($password, "\r")) {
            throw new \RuntimeException('DB_PASS must not contain line breaks.');
        }

        return $password;
    }

    public function databaseHost(): string
    {
        return self::required($this->dbHost, 'DB_HOST');
    }

    public function databasePrefix(): string
    {
        return self::required($this->dbPrefix, 'DB_PREFIX');
    }

    public function productionHost(): string
    {
        return self::required($this->prodHost, 'PROD_HOST');
    }

    public function developmentHost(): string
    {
        return self::required($this->devHost, 'DEV_HOST');
    }

    /** @return array<string, string|null> */
    public function __debugInfo(): array
    {
        return [
            'dbName' => $this->dbName,
            'dbUser' => $this->dbUser,
            'dbPassword' => null === $this->dbPassword ? null : '[redacted]',
            'dbHost' => $this->dbHost,
            'dbPrefix' => $this->dbPrefix,
            'prodHost' => $this->prodHost,
            'devHost' => $this->devHost,
        ];
    }

    private static function read(string $name): ?string
    {
        $value = getenv($name);

        return false === $value ? null : $value;
    }

    private static function required(?string $value, string $name, bool $allowEmpty = false): string
    {
        if (null === $value || (!$allowEmpty && '' === $value)) {
            throw new \RuntimeException('Missing environment variable: '.$name);
        }

        return $value;
    }
}
