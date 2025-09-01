<?php

namespace Andrey\PhpMig;

use PDO;
class Db
{
    protected $conn;
    public function __construct(string $dsn, string $user, string $pass, array $options)
    {
        $this->conn = new PDO(
            $dsn,
            $user,
            $pass,
            $options
        );
    }
}