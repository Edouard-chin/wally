<?php

namespace SocialWallBundle\Monolog\Formatter;

use Monolog\Formatter\ElasticaFormatter as BaseFormatter;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Elastica\Document;

class ElasticaFormatter extends BaseFormatter
{
    private $tokenStorage;

    public function __construct(TokenStorage $tokenStorage, $index, $type)
    {
        $this->tokenStorage = $tokenStorage;
        parent::__construct($index, $type);
    }

    /**
     * {@inheritDoc}
     */
    protected function getDocument($record)
    {
        $user = $this->tokenStorage->getToken()->getUser();
        $record['extra']['user'] = $user->getUsername();
        $document = new Document();
        $document->setData($record);
        $document->setType($this->type);
        $document->setIndex($this->index);

        return $document;
    }
}
