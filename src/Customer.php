<?php

namespace Andrey\PhpMig;

class Customer
{
    public static function b3ToDlId(string $b3Id): int {
        if (substr($b3Id, 0, strlen(self::getPrefix())) == self::getPrefix()) {
            $id = substr($b3Id, strlen(self::getPrefix()));
        }
        else {
            $id = $b3Id;
        }
        if (filter_var($id, FILTER_VALIDATE_INT) === false) {
            throw new \Exception("invalid id: $id");
        }
        return (int)$id;
    }

    public static function DlToB3Id(int $b3Id): string {
        return self::getPrefix() . $b3Id;
    }

    public static function getPrefix(): string {
        return getenv('CUSTOMER_PREFIX');
    }
}