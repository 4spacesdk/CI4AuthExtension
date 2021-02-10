<?php namespace AuthExtension\OAuth2;

use OAuth2\RequestInterface;
use OAuth2\Scope;

class ScopeUtil extends Scope {

    public function getScopeFromRequest(RequestInterface $request) {
        $requestedScopes = parent::getScopeFromRequest($request);
        $defaultScopes = parent::getDefaultScope();
        return implode(' ', array_unique(array_merge(
            strlen($requestedScopes) ? explode(' ', $requestedScopes): [],
            strlen($defaultScopes) ? explode(' ', $defaultScopes): []
        )));
    }

}
