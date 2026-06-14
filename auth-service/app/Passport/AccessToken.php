<?php

namespace App\Passport;

use Laravel\Passport\Bridge\AccessToken as PassportAccessToken;
use App\Models\User;

class AccessToken extends PassportAccessToken
{
    /**
     * Generate a JWT from the access token
     *
     * @return \Lcobucci\JWT\Token\Plain
     */
    public function convertToJWT()
    {
        $this->initJwtConfiguration();

        $getter = function () {
            return $this->jwtConfiguration;
        };
        $jwtConfiguration = $getter->bindTo($this, \Laravel\Passport\Bridge\AccessToken::class)();

        $builder = $jwtConfiguration->builder()
            ->permittedFor($this->getClient()->getIdentifier())
            ->identifiedBy($this->getIdentifier())
            ->issuedAt(new \DateTimeImmutable())
            ->canOnlyBeUsedAfter(new \DateTimeImmutable())
            ->expiresAt($this->getExpiryDateTime())
            ->relatedTo((string) $this->getUserIdentifier())
            ->withClaim('scopes', $this->getScopes());

        // Add our custom claims
        $userIdentifier = $this->getUserIdentifier();
        $user = User::with(['profile.role', 'profile.department'])->find($userIdentifier);
        \Illuminate\Support\Facades\Log::info("App\Passport\AccessToken::convertToJWT called with userIdentifier: " . json_encode($userIdentifier) . ". Found user: " . ($user ? 'Yes' : 'No'));
        
        if ($user) {
            $builder = $builder->withClaim('role', $user->profile?->role?->name)
                               ->withClaim('department', $user->profile?->department?->name)
                               ->withClaim('email', $user->email)
                               ->withClaim('first_name', $user->profile?->first_name)
                               ->withClaim('last_name', $user->profile?->last_name);
        }

        return $builder
            ->getToken($jwtConfiguration->signer(), $jwtConfiguration->signingKey());
    }

    /**
     * Generate a string representation from the access token
     */
    public function toString(): string
    {
        return $this->convertToJWT()->toString();
    }
}
