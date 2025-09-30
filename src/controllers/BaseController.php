<?php

namespace App\controllers;

use Doctrine\ORM\EntityManager; // kept for BC but not type hinted
use Psr\Container\ContainerInterface;

abstract class BaseController
{
    protected ?ContainerInterface $container;
    protected EntityManager $entityManager;

    public function getContainer(): ?ContainerInterface
    {
        return $this->container;
    }

    public function setContainer(?ContainerInterface $container): void
    {
        $this->container = $container;
    }


    public function getEntityManager()
    {
        return $this->entityManager;
    }

    public function setEntityManager($entityManager): void
    {
        $this->entityManager = $entityManager;
    }
    public function __construct(?ContainerInterface $container, EntityManager $entityManager)
    {
        $this->container = $container;
        $this->entityManager = $entityManager;
    }

}