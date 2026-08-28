<?php

/*
 * DatabaseConfigSslOptionsTest.php
 * Copyright (c) 2026 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tests\unit\Config;

use Override;
use Pdo\Mysql;
use Tests\integration\TestCase;

/**
 * @group unit-test
 * @group config
 *
 * @internal
 *
 * @coversNothing
 */
final class DatabaseConfigSslOptionsTest extends TestCase
{
    private const SSL_VARS = ['MYSQL_USE_SSL', 'MYSQL_SSL_CAPATH', 'MYSQL_SSL_CA', 'MYSQL_SSL_CERT', 'MYSQL_SSL_KEY', 'MYSQL_SSL_CIPHER', 'MYSQL_SSL_VERIFY_SERVER_CERT'];

    private array $originals = [];

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        foreach (self::SSL_VARS as $name) {
            $this->originals[$name] = [
                '_ENV'    => $_ENV[$name] ?? null,
                '_SERVER' => $_SERVER[$name] ?? null,
                'putenv'  => getenv($name) === false ? null : getenv($name),
            ];
        }
    }

    #[Override]
    protected function tearDown(): void
    {
        foreach (self::SSL_VARS as $name) {
            if (null === $this->originals[$name]['_ENV']) {
                unset($_ENV[$name]);
            } else {
                $_ENV[$name] = $this->originals[$name]['_ENV'];
            }
            if (null === $this->originals[$name]['_SERVER']) {
                unset($_SERVER[$name]);
            } else {
                $_SERVER[$name] = $this->originals[$name]['_SERVER'];
            }
            putenv($name);
            if (null !== $this->originals[$name]['putenv']) {
                putenv(sprintf('%s=%s', $name, $this->originals[$name]['putenv']));
            }
        }
        parent::tearDown();
    }

    private function setEnvVar(string $name, string $value): void
    {
        $_ENV[$name]    = $value;
        $_SERVER[$name] = $value;
        putenv(sprintf('%s=%s', $name, $value));
    }

    private function unsetEnvVar(string $name): void
    {
        unset($_ENV[$name], $_SERVER[$name]);
        putenv($name);
    }

    public function testEmptySslEnvVarsAreTreatedAsUnset(): void
    {
        $this->setEnvVar('MYSQL_USE_SSL', 'true');
        $this->setEnvVar('MYSQL_SSL_CA', '/tmp/ca.crt');
        $this->setEnvVar('MYSQL_SSL_CERT', '');
        $this->setEnvVar('MYSQL_SSL_KEY', '');
        $this->setEnvVar('MYSQL_SSL_CIPHER', '');
        $this->setEnvVar('MYSQL_SSL_CAPATH', '');
        $this->setEnvVar('MYSQL_SSL_VERIFY_SERVER_CERT', '');

        $config = include config_path('database.php');

        $options = $config['connections']['mysql']['options'];
        $this->assertSame([Mysql::ATTR_SSL_CA => '/tmp/ca.crt'], $options);
    }

    public function testSslEnvVarsNotSetYieldsNoSslOptions(): void
    {
        $this->setEnvVar('MYSQL_USE_SSL', 'true');
        foreach (['MYSQL_SSL_CAPATH', 'MYSQL_SSL_CA', 'MYSQL_SSL_CERT', 'MYSQL_SSL_KEY', 'MYSQL_SSL_CIPHER', 'MYSQL_SSL_VERIFY_SERVER_CERT'] as $name) {
            $this->unsetEnvVar($name);
        }

        $config = include config_path('database.php');

        $options = $config['connections']['mysql']['options'];
        $this->assertSame([], $options);
    }

    public function testNonEmptySslEnvVarsArePassedThrough(): void
    {
        $this->setEnvVar('MYSQL_USE_SSL', 'true');
        $this->setEnvVar('MYSQL_SSL_CAPATH', '/tmp/certs');
        $this->setEnvVar('MYSQL_SSL_CA', '/tmp/ca.crt');
        $this->setEnvVar('MYSQL_SSL_CERT', '/tmp/client.crt');
        $this->setEnvVar('MYSQL_SSL_KEY', '/tmp/client.key');
        $this->setEnvVar('MYSQL_SSL_CIPHER', 'DHE-RSA-AES256-SHA');
        $this->setEnvVar('MYSQL_SSL_VERIFY_SERVER_CERT', 'false');

        $config = include config_path('database.php');

        $options = $config['connections']['mysql']['options'];
        $this->assertSame(
            [
                Mysql::ATTR_SSL_CAPATH            => '/tmp/certs',
                Mysql::ATTR_SSL_CA                => '/tmp/ca.crt',
                Mysql::ATTR_SSL_CERT              => '/tmp/client.crt',
                Mysql::ATTR_SSL_KEY               => '/tmp/client.key',
                Mysql::ATTR_SSL_CIPHER            => 'DHE-RSA-AES256-SHA',
                Mysql::ATTR_SSL_VERIFY_SERVER_CERT => false,
            ],
            $options
        );
    }
}
