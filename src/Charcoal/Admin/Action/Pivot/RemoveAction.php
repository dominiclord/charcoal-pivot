<?php

namespace Charcoal\Admin\Action\Pivot;

use \Exception;

use \Pimple\Container;

// Dependencies from PSR-7 (HTTP Messaging)
use \Psr\Http\Message\RequestInterface;
use \Psr\Http\Message\ResponseInterface;

// From 'charcoal-admin'
use \Charcoal\Admin\AdminAction;

// From 'charcoal-core'
use \Charcoal\Loader\CollectionLoader;

// From 'charcoal-pivot'
use \Charcoal\Pivot\Object\Pivot;

/**
 * Disconnect two objects
 */
class RemoveAction extends AdminAction
{
    /**
     * @param RequestInterface  $request  A PSR-7 compatible Request instance.
     * @param ResponseInterface $response A PSR-7 compatible Response instance.
     * @return ResponseInterface
     */
    public function run(RequestInterface $request, ResponseInterface $response)
    {
        $params = $request->getParams();

        if (
            !isset($params['pivot_id'])
        ) {
            $this->setSuccess(false);

            return $response;
        }

        $pivotId = $params['pivot_id'];

        // Try loading the object
        try {
            $obj = $this->modelFactory()->create($sourceObjType)->load($sourceObjId);
        } catch (Exception $e) {
            $this->setSuccess(false);

            return $response;
        }

        $pivotProto = $this->modelFactory()->create(Pivot::class);
        if (!$pivotProto->source()->tableExists()) {
            $pivotProto->source()->createTable();
        }

        $loader = new CollectionLoader([
            'logger'  => $this->logger,
            'factory' => $this->modelFactory()
        ]);
        $pivotModel = $loader
            ->setModel($pivotProto)
            ->load($pivotId);

        $pivotModel->delete();

        $this->setSuccess(true);

        return $response;
    }
}
