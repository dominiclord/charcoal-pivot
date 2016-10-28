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
 * Associate two objects by creating a Pivot.
 */
class CreateAction extends AdminAction
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
            !isset($params['pivots']) ||
            !isset($params['source_obj_id']) ||
            !isset($params['source_obj_type']) ||
            !isset($params['target_obj_type'])
        ) {
            $this->setSuccess(false);

            return $response;
        }

        $pivots = $params['pivots'];
        $sourceObjId = $params['source_obj_id'];
        $sourceObjType = $params['source_obj_type'];
        $targetObjType = $params['target_obj_type'];

        // Need more pivots...
        if (!count($pivots)) {
            $this->setSuccess(false);

            return $response;
        }

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

        // Clean all previously created pivots and start anew
        $loader = new CollectionLoader([
            'logger'  => $this->logger,
            'factory' => $this->modelFactory()
        ]);
        $loader
            ->setModel($pivotProto)
            ->addFilter('source_object_type', $sourceObjType)
            ->addFilter('source_object_id', $sourceObjId)
            ->addFilter('target_object_type', $targetObjType)
            ->addOrder('position', 'asc');

        $existingPivots = $loader->load();

        foreach ($existingPivots as $j) {
            $j->delete();
        }

        $count = count($pivots);
        $i = 0;
        for (; $i<$count; $i++) {
            $targetObjId = $pivots[$i]['target_obj_id'];
            $position = $pivots[$i]['position'];

            $pivotModel = $this->modelFactory()->create(Pivot::class);
            $pivotModel
                ->setSourceObjectType($sourceObjType)
                ->setSourceObjectId($sourceObjId)
                ->setTargetObjectType($targetObjType)
                ->setTargetObjectId($targetObjId)
                ->setPosition($position);

            $pivotModel->save();
        }

        $this->setSuccess(true);

        return $response;
    }
}
