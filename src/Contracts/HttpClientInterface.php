<?php

declare(strict_types=1);

namespace Triyatna\ValidGame\Contracts;

interface HttpClientInterface
{
    /**
     * @param array<string, string> $headers
     * @param array<string, string|int|float|null> $form
     * @param array{timeout?:float,connect_timeout?:float,proxy?:string|null,debug?:bool} $options
     * @return array{status:int, headers:array<string,array<int,string>>, body:string}
     */
    public function postForm(string $url, array $headers, array $form, array $options = []): array;

    /**
     * @param array<string, string> $headers
     * @param array{timeout?:float,connect_timeout?:float,proxy?:string|null,debug?:bool} $options
     * @return array{status:int, headers:array<string,array<int,string>>, body:string}
     */
    public function get(string $url, array $headers = [], array $options = []): array;
}
