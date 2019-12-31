<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\Model;

use Laminas\ApiTools\ApiProblem\ApiProblem;
use Laminas\ApiTools\Rest\AbstractResourceListener;
use Laminas\ApiTools\Rest\Exception\CreationException;
use MongoCollection;
use MongoCursor;
use MongoException;
use MongoId;

class MongoConnectedListener extends AbstractResourceListener
{
    /**
     * @var MongoCollection
     */
    protected $collection;

    /**
     * @param MongoCollection $collection
     */
    public function __construct(MongoCollection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * Create a new document in the MongoCollection
     *
     * @param  array|object $data
     * @return boolean
     * @throws CreationException
     */
    public function create($data)
    {
        if (is_object($data)) {
            $data = (array) $data;
        }

        try {
            $result = $this->collection->insert($data);
        } catch (MongoException $e) {
            throw new CreationException('MongoDB error: ' . $e->getMessage());
        }
        $data['_id'] = (string) $data['_id'];
        return $data;
    }

    /**
     * Update of a document specified by id
     *
     * @param  string $id
     * @param  array $data
     * @return boolean
     */
    public function patch($id, $data)
    {
        $result = $this->collection->update(
            array( '_id' => new MongoId($id) ),
            array( '$set' => $data )
        );

        if (isset($result['ok']) && $result['ok']) {
            return true;
        }
        return ($result === true);
    }

    /**
     * Fetch data in a collection using the id
     *
     * @param  string $id
     * @return array
     */
    public function fetch($id)
    {
        $result = $this->collection->findOne(array(
            '_id' => new MongoId($id)
        ));

        if (null === $result) {
            return new ApiProblem(404, 'Document not found in the collection');
        }
        $result['_id'] = (string) $result['_id'];
        return $result;
    }

    /**
     * Fetch all data in a collection
     *
     * @param  array $params
     * @return MongoCursor
     */
    public function fetchAll($params = array())
    {
        // @todo How to handle the pagination?
        $rows = $this->collection->find($params);
        $result = array();
        foreach ($rows as $id => $collection) {
            unset($collection['_id']);
            $result[$id] = $collection;
        }
        return $result;
    }

    /**
     * Delete a document in a collection
     *
     * @param  string $id
     * @return boolean
     */
    public function delete($id)
    {
        $result = $this->collection->remove(array(
            '_id' => new MongoId($id)
        ));
        if (isset($result['ok']) && $result['ok']) {
            return true;
        }
        return ($result === true);
    }
}
