<?php

namespace Andrey\PhpMig;

use \PDO;
class AppDb
{
    private PDO $pdo;

    const STATUS_ERR = 'error';
    const STATUS_DONE = 'done';
    const STATUS_WAIT = 'wait';
    private string $tableName;

    function __construct($dsn, $user, $pass)
    {
        $this->tableName = getenv('APP_DB_TABLE');
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_PERSISTENT => true
        ];

        $this->pdo = new PDO($dsn, $user, $pass, $options);
    }

    public function add(
        string  $token,
        int     $customerId,
        ?string $b3Address = null,
        ?string $dlAddress = null,
        ?string $validateStatus = null,
        ?string $updateB3Status = null,
        ?string $validateComment = null,
        ?string $updateComment = null
    )
    {
        $sql = "
    INSERT IGNORE INTO $this->tableName 
    (token, customer_id, b3_address, dl_address, validate_status, update_b3_status, validate_comment, update_comment) 
    VALUES 
    (:token, :customer_id, :b3_address, :dl_address, :validate_status, :update_b3_status, :validate_comment, :update_comment)";

        $stmt = $this->pdo->prepare($sql);

        if (is_null($validateStatus)) {
            $validateStatus = self::STATUS_WAIT;
        }
        if (is_null($updateB3Status)) {
            $updateB3Status = self::STATUS_WAIT;
        }
        $values = [
            ':token' => $token,
            ':customer_id' => $customerId,
            ':b3_address' => $b3Address,
            ':dl_address' => $dlAddress,
            ':validate_status' => $validateStatus,
            ':update_b3_status' => $updateB3Status,
            ':validate_comment' => $validateComment,
            ':update_comment' => $updateComment,
        ];

        $stmt->execute($values);
    }


    public function getRowIterator(int $limit = null, int $offset = null): \Generator
    {
        $sql = "SELECT * FROM $this->tableName order by id";
        if (!is_null($limit)) {
            $sql .= " limit $limit";
            if (!is_null($offset)) {
                $sql .= " offset $offset";
            }
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            yield $row;
        }
    }


    public function updateDlAddress($id, $address)
    {
        $sql = "UPDATE $this->tableName SET dl_address = :dl_address WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);

        $values = [
            ':id' => $id,
            ':dl_address' => $address
        ];

        $stmt->execute($values);
    }

    public function updateValidateStatusAndComment($id, string $validateStatus, ?string $comment = null)
    {
        $sql = "UPDATE $this->tableName SET validate_status = :validate_status, validate_comment = :comment WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);

        $values = [
            ':id' => $id,
            ':validate_status' => $validateStatus,
            ':comment' => $comment
        ];

        $stmt->execute($values);
    }

    public function updateUpdateStatusAndComment($id, string $status, ?string $comment = null)
    {
        $sql = "UPDATE $this->tableName SET update_b3_status = :update_b3_status, update_comment = :comment WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);

        $values = [
            ':id' => $id,
            ':update_b3_status' => $status,
            ':comment' => $comment
        ];

        $stmt->execute($values);
    }

}