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
            !isset($params['source_obj_id']) ||
            !isset($params['source_obj_type']) ||
            !isset($params['target_obj_id']) ||
            !isset($params['target_obj_type'])
        ) {
            $this->setSuccess(false);

            return $response;
        }

        $sourceObjId = $params['source_obj_id'];
        $sourceObjType = $params['source_obj_type'];
        $targetObjId = $params['target_obj_id'];
        $targetObjType = $params['target_obj_type'];

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
        $loader
            ->setModel($pivotProto)
            ->addFilter('source_object_type', $sourceObjType)
            ->addFilter('source_object_id', $sourceObjId)
            ->addFilter('target_obj_id', $targetObjId)
            ->addFilter('target_obj_type', $group);

        $existingPivots = $loader->load();

        // Should be just one, tho.
        foreach ($existingPivots as $j) {
            $j->delete();
        }

        $this->setSuccess(true);

        return $response;
    }
}
