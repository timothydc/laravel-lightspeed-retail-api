<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Interfaces;

use Illuminate\Database\Eloquent\Model;

interface TokenInterface
{
    public function exists(): bool;

    public function saveToken(array $data): Model;

    public function createToken(): Model;

    public function getToken(): Model;

    public function getAccessToken(): string;

    public function getRefreshToken(): string;
}
