<?php

namespace Andrey\PhpMig;

class Customer
{
    const PREFIX = 'dl-';
    public static function b3ToDlId(string $b3Id): int {
        if (substr($b3Id, 0, strlen(self::PREFIX)) == self::PREFIX) {
            return substr($b3Id, strlen(self::PREFIX));
        }
        else {
            return $b3Id;
        }
    }

    public static function DlToB3Id(int $b3Id): string {
        return self::PREFIX . $b3Id;
    }
}