<?php

namespace App\Passport;

use Laravel\Passport\Bridge\AccessTokenRepository as PassportAccessTokenRepository;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;

class AccessTokenRepository extends PassportAccessTokenRepository
{
    /**
     * {@inheritdoc}
     */
    public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, ?string $userIdentifier = null): AccessTokenEntityInterface
    {
        \Illuminate\Support\Facades\Log::info("App\Passport\AccessTokenRepository::getNewToken called");
        return new AccessToken($userIdentifier, $scopes, $clientEntity);
    }
}
