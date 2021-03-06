<?php

namespace Honeybee\Infrastructure\DataAccess\Connector;

use PDO;

class PdoConnector extends Connector
{
    const DSN_PATTERN = '%s:host=%s;port=%d;dbname=%s';

    const DEFAULT_HOST = '127.0.0.1';

    const DEFAULT_CHARSET = 'UTF8';

    protected function connect()
    {
        $this->needs('adapter')->needs('database')->needs('port')->needs('user')->needs('password');

        $adapter = $this->config->get('adapter');
        $host = $this->config->get('host', self::DEFAULT_HOST);
        $port = $this->config->get('port');
        $database = $this->config->get('database');
        $charset = $this->config->get('charset', self::DEFAULT_CHARSET);

        $connection_dsn = sprintf(self::DSN_PATTERN, $adapter, $host, $port, $database, $charset);
        $database_user = $this->config->get('user');
        $database_password = $this->config->get('password');

        return new PDO($connection_dsn, $database_password, $database_password);
    }
}
