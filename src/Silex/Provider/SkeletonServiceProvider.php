<?php

namespace Silex\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Service provider for Silex
 */
class SkeletonServiceProvider implements ServiceProviderInterface
{

    /**
     * Register Service Provider
     * @param Application $app Silex application instance
     */
    public function register(Application $app)
    {

    }


    /**
     * Boot Method
     * @param Application $app Silex application instance
     * @codeCoverageIgnore
     */
    public function boot(Application $app)
    {
    }
}
