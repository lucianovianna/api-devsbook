<?php

namespace App\Auth;

use Tymon\JWTAuth\Claims\Factory;

class JwtClaimFactory extends Factory
{
    public function setTTL($ttl)
    {
        // jwt-auth 2.3 casts null to zero, despite supporting an omitted exp claim.
        if ($ttl === null) {
            $this->ttl = null;

            return $this;
        }

        return parent::setTTL($ttl);
    }
}
