<?php
declare(strict_types=1);

namespace App\Util;

use InvalidArgumentException;

final class SafelyArrayJsonEncoder
{
    public function encode(array $subject, int $options = 0, int $depth = 512): string
    {
        $jsonEncodedData = json_encode($subject, $options, $depth);

        if ($jsonEncodedData === false) {
            throw new InvalidArgumentException('Problem with json-encoding', 0, null);
        }

        return $jsonEncodedData;
    }

    public function decode(string $subject, bool $assoc = true, int $depth = 512, int $options = 0): array
    {
        $decodedData = json_decode($subject, $assoc, $depth, $options);

        if (is_null($decodedData)) {
            throw new InvalidArgumentException('Problem with json-decoding', 0, null);
        }

        return $decodedData;
    }
}