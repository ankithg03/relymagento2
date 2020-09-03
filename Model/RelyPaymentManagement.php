<?php

namespace Rely\Payment\Model;

use Magento\Framework\Exception\AlreadyExistsException;
use Rely\Payment\Api\RelyPaymentManagementInterface;
use Rely\Payment\Model\RelyPayment as Model;
use Rely\Payment\Model\RelyPaymentFactory as ModelFactory;
use Rely\Payment\Model\ResourceModel\RelyPayment as ResourceModel;
use Rely\Payment\Model\ResourceModel\RelyPayment\Collection;
use Rely\Payment\Model\ResourceModel\RelyPayment\CollectionFactory;

/**
 *
 * @description Magento Module for Rely Payment
 * @author   Codilar Team Player <ankith@codilar.com>
 * @license  Open Source
 * @link     https://www.codilar.com
 * @copyright Copyright © 2020 Codilar Technologies Pvt. Ltd.. All rights reserved
 *
 */

class RelyPaymentManagement implements RelyPaymentManagementInterface
{

    /**
     * @var ModelFactory
     */
    private $modelFactory;
    /**
     * @var ResourceModel
     */
    private $resourceModel;
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * TokenManagement constructor.
     * @param ModelFactory $modelFactory
     * @param ResourceModel $resourceModel
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        ModelFactory $modelFactory,
        ResourceModel $resourceModel,
        CollectionFactory $collectionFactory
    ) {
        $this->modelFactory = $modelFactory;
        $this->resourceModel = $resourceModel;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @inheritDoc
     */
    public function getDataBYId($id)
    {
        return $this->load($id);
    }

    /**
     * @inheritDoc
     * @throws AlreadyExistsException
     */
    public function save(Model $model)
    {
        $this->resourceModel->save($model);
        return $model;
    }

    /**
     * @inheritDoc
     */
    public function afterSave(Model $model)
    {
        $this->resourceModel->afterSave($model);
        return $model;
    }

    /**
     * @inheritDoc
     */
    public function load($value, $field = null)
    {
        $model = $this->create();
        $this->resourceModel->load($model, $value, $field);
        return $model;
    }

    /**
     * @inheritDoc
     */
    public function create()
    {
        return $this->modelFactory->create();
    }

    /**
     * @inheritDoc
     */
    public function delete(Model $model)
    {
        try {
            $this->resourceModel->delete($model);
        } catch (\Exception $exception) {
            return false;
        }
        return $this;
    }

    /**
     * @return Collection
     */
    public function getCollection()
    {
        return $this->collectionFactory->create();
    }

    /**
     * @inheritDoc
     */
    public function deleteById($id)
    {
        $model = $this->load($id);
        return $this->delete($model);
    }
}
