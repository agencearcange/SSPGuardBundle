<?php
/*
 * This file is part of the SSPGuardBundle.
 *
 * (c) Sergio Gómez <sergio@uco.es>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Sgomez\Bundle\SSPGuardBundle\Security\Authenticator;


use Sgomez\Bundle\SSPGuardBundle\SimpleSAMLphp\AuthSourceRegistry;
use Sgomez\Bundle\SSPGuardBundle\SimpleSAMLphp\SSPAuthSource;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

abstract class SSPGuardAuthenticator extends AbstractGuardAuthenticator
{
    /**
     * @var Router
     */
    protected $router;
    /**
     * @var AuthSourceRegistry
     */
    protected $authSourceRegistry;
    /**
     * @var SSPAuthSource
     */
    protected $authSource = null;
    /**
     * @var
     */
    protected $authSourceId;

    /**
     * SSPGuardAuthenticator constructor.
     *
     * @param Router $router
     * @param AuthSourceRegistry $authSourceRegistry
     */
    public function __construct(Router $router, AuthSourceRegistry $authSourceRegistry, $authSourceId)
    {
        $this->router = $router;
        $this->authSourceRegistry = $authSourceRegistry;
        $this->authSourceId = $authSourceId;
    }


    /**
     * {@inheritdoc}
     */
    public function getCredentials(Request $request)
    {
        $this->authSource = $this->authSourceRegistry->getAuthSource($this->authSourceId);

        return $this->authSource->getCredentials();
    }

    /**
     * {@inheritdoc}
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        return $this->authSource && $this->authSource->isAuthenticated();
    }

    /**
     * Helper to save the authentication exception into the session
     *
     * @param Request $request
     * @param AuthenticationException $exception
     */
    protected function saveAuthenticationErrorToSession(Request $request, AuthenticationException $exception)
    {
        $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
    }

    /**
     * Returns the URL (if any) the user visited that forced them to login.
     *
     * @param Request $request
     * @param $providerKey
     *
     * @return string|null
     */
    protected function getTargetPath(Request $request, $providerKey)
    {
        return $request->getSession()->get('_security.'.$providerKey.'.target_path');
    }

    /**
     * Does the authenticator support the given Request?
     *
     * If this returns false, the authenticator will be skipped.
     *
     * @param Request $request
     *
     * @return bool
     */
    public function supports(Request $request)
    {
        $match = $this->router->match($request->getPathInfo());

        if ('ssp_guard_check' !== $match['_route'] || $this->authSourceId !== $match['authSource']) {
            // skip authentication unless we're on this route
            return false;
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsRememberMe()
    {
        return true;
    }
}