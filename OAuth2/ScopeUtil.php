<?php namespace AuthExtension\OAuth2;

use OAuth2\RequestInterface;
use OAuth2\Scope;

class ScopeUtil extends Scope {

    public function getScopeFromRequest(RequestInterface $request): string {
        $requestedScopes = parent::getScopeFromRequest($request);
        $defaultScopes = parent::getDefaultScope();
        return implode(' ', array_unique(array_merge(
            (string)$requestedScopes !== '' ? explode(' ', $requestedScopes) : [],
            (string)$defaultScopes !== '' ? explode(' ', $defaultScopes) : []
        )));
    }

}
