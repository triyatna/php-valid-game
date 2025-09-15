<?php

declare(strict_types=1);

namespace Triyatna\ValidGame\Support;

final class OrderProfileEncoder
{
    public static function encode(string $name = "", string $dob = "", string $idNo = ""): string
    {
        $json = json_encode([
            'name'        => $name,
            'dateofbirth' => $dob,
            'id_no'       => $idNo,
        ], JSON_UNESCAPED_SLASHES);
        return base64_encode($json ?: '{}');
    }
}
