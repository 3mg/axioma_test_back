<?php
/**
 * Created by PhpStorm.
 * User: nickolay
 * Date: 06.11.15
 * Time: 12:15
 */

namespace AppBundle\Faker;

use JMS\DiExtraBundle\Annotation as DI;
use Nelmio\Alice\Instances\Processor\Methods\Faker;

/**
 * @DI\Service("app.faker.processor_method_factory", public=true)
 */
class FakerProcessorMethodFactory
{
    protected $provider_chain;

    /**
     * @DI\InjectParams({
     *     "provider_chain" = @DI\Inject("hautelook_alice.faker.provider_chain", required=false),
     * })
     */
    function __construct($provider_chain)
    {
        $this->provider_chain = $provider_chain;
    }

    public function createFakerProcessorMethod($locale = "en_US") {
        $providers = $this->provider_chain->getProviders();
        $fakerProcessorMethod = new Faker($providers);

        return $fakerProcessorMethod;
    }
}