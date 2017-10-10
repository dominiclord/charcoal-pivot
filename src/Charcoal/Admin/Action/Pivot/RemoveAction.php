<?php

namespace Charcoal\Admin\Action\Pivot;

use Exception;

// From Pimple
use Pimple\Container;

// From PSR-7
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

// From 'charcoal-admin'
use Charcoal\Admin\AdminAction;

// From 'charcoal-core'
use Charcoal\Loader\CollectionLoader;

// From 'charcoal-pivot'
use Charcoal\Pivot\Object\Pivot;

/**
 * Disconnect two objects
 */
class RemoveAction extends AdminAction
{
    /**
     * @param  RequestInterface  $request  A PSR-7 compatible Request instance.
     * @param  ResponseInterface $response A PSR-7 compatible Response instance.
     * @return ResponseInterface
     *     Expected HTTP status codes:
     *     - `400`: If the "pivot_id" parameter is missing.
     *     - `404`: If the "pivot_id" object is invalid or cannot be found.
     *     - `418`: If the {@see Pivot} source does not exist.
     *     - `200`: If the request was fulfilled.
     */
    public function run(RequestInterface $request, ResponseInterface $response)
    {
        $pivotId   = $request->getParam('pivot_id');

        if (!$pivotId) {
            $this->setSuccess(false);
            $this->addFeedback('error', 'Missing "pivot_id" for detaching objects.');
            return $response->withStatus(400);
        }

        $pivot = $this->modelFactory()->create(Pivot::class);
        if (!$pivot->source()->tableExists()) {
            $this->setSuccess(false);
            $this->addFeedback('warning', 'Missing relationships table.');
            return $response->withStatus(418);
        }

        $pivot->load($pivotId);
        if (!$pivot->id()) {
            $this->setSuccess(false);
            $this->addFeedback('error', 'The relationship cannot be found.');
            return $response->withStatus(404);
        }

        $deleteObj = $request->getParam('delete_obj', false);
        $deleteObj = filter_var($deleteObj, FILTER_VALIDATE_BOOLEAN);

        if ($deleteObj) {
            $obj = $this->modelFactory()->create($pivot->targetObjectType());
            if (!$obj->source()->tableExists()) {
                $this->setSuccess(false);
                $this->addFeedback('warning', 'Missing related table.');
                return $response->withStatus(418);
            }

            $obj->load($pivot->targetObjectId());
            if (!$obj->id()) {
                $this->setSuccess(false);
                $this->addFeedback('error', 'The related target cannot be found.');
                return $response->withStatus(404);
            }

            if ($obj->delete()) {
                $this->addFeedback('success', 'The related object is deleted.');
            } else {
                $this->addFeedback('error', 'The related object could not be deleted.');
            }
        }

        $result = $pivot->delete();
        $this->setSuccess($result);

        if ($result) {
            $this->addFeedback('success', 'The relationship is detached.');
        } else {
            $this->addFeedback('error', 'The relationship could not be detached.');
        }


        return $response;
    }
}
