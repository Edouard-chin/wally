<?php

namespace SocialWallBundle\Services;

class StatUtils
{
    private $searchObject;

    public function __construct($index)
    {
        $client = (new \Elastica\Client())
            ->addConnection(new \Elastica\Connection());
        $this->searchObject = new \Elastica\Search($client);
        $this->searchObject->addIndex($index);
    }

    public function filterForPeriod(\DateTime $from = null, \DateTime $to = null)
    {
        $from = $from ?: new \DateTime('-2 weeks');
        $to = $to?: new \DateTime();
        $filterRange = new \Elastica\Filter\Range('datetime', [
            'gte' => $from->format(\DateTime::ISO8601),
            'lte' => $to->format(\DateTime::ISO8601),
        ]);

        $dateAggregation = (new \Elastica\Aggregation\DateHistogram('dateAggregation', 'datetime', 'day'))
            ->setTimezone('02:00')
            ->setFormat('dd/MM/YYYY')
            ->setMinimumDocumentCount(0)
            ->setParam('extended_bounds', ['min' => $from->format('d/m/Y'), 'max' => $to->format('d/m/Y')]);

        $filteredQuery = (new \Elastica\Query\Filtered())
            ->setFilter($filterRange);

        $query = new \Elastica\Query();
        $query
            ->setQuery($filteredQuery)
            ->addAggregation($dateAggregation);
        $this->searchObject->setOptionsAndQuery([], $query);
        $resultSet = $this->searchObject->search();

        return [$resultSet->getAggregation('dateAggregation')['buckets'], $this->searchObject->count()];
    }

    public function allTimeVisit()
    {
        $query = new \Elastica\Query();
        $matchQuery = new \Elastica\Query\MatchAll();
        $query->setQuery($matchQuery);
        $this->searchObject->setOptionsAndQuery([], $query);

        return $this->searchObject->count();
    }
}
