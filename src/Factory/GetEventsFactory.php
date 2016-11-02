<?php

namespace CQRSApi\Factory;

use CQRSApi\Action\GetEventsAction;
use Interop\Container\ContainerInterface;

class GetEventsFactory
{
    public function __invoke(ContainerInterface $container): GetEventsAction
    {
        return new GetEventsAction(
            $container->get('cqrs.event_store.table')
        );
    }
}
