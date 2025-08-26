<?php

namespace Andrey\PhpMig;

class Address {
    public string $customerId;
    public ?string $line1 = null;
    public ?string $line2 = null;
    public ?string $line3 = null;
    public ?string $city = null;
    public ?string $region = null;
    public ?string $country = null;
    public ?string $postalCode = null;
    public ?string $company = null;
    public ?string $filledBy = null;

    public function isFull(): bool {
        return (!empty($this->line1) && !empty($this->city) && !empty($this->region)) ||
            (!empty($this->line1) && !empty($this->postalCode));
    }


    public function toJson(): string
    {
        return json_encode(get_object_vars($this));
    }

    public static function fromJson(string $json): ?self
    {
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        $object = new self();
        foreach ($data as $property => $value) {
            if (property_exists($object, $property)) {
                $object->$property = $value;
            }
        }

        return $object;
    }
}
