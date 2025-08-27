<?php

namespace Andrey\PhpMig;

class Customer
{
    public static function b3ToDlId(string $b3Id): int {
        if (substr($b3Id, 0, strlen(self::getPrefix())) == self::getPrefix()) {
            return substr($b3Id, strlen(self::getPrefix()));
        }
        else {
            return $b3Id;
        }
    }

    public static function DlToB3Id(int $b3Id): string {
        return self::getPrefix() . $b3Id;
    }

    public static function getPrefix(): string {
        return getenv('CUSTOMER_PREFIX') . '-';
    }
}