<?php

namespace SumoCoders\FrameworkCoreBundle\Pagination;

use Doctrine\ORM\QueryBuilder as DoctrineQueryBuilder;
use Doctrine\ORM\Tools\Pagination\CountWalker;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use ArrayIterator;
use IteratorAggregate;
use Traversable;
use Countable;
use Iterator;

class Paginator implements Countable, IteratorAggregate
{
    public const PAGE_SIZE = 30;

    private int $currentPage;
    private int $startPage;
    private int $endPage;
    private Traversable $results;
    private int $numResults;

    public function __construct(
        private DoctrineQueryBuilder $queryBuilder,
        private int $pageSize = self::PAGE_SIZE,
    ) {
    }

    public function paginate(int $page = 1): self
    {
        $this->currentPage = max(1, $page);
        $firstResult = ($this->currentPage - 1) * $this->pageSize;

        $query = $this->queryBuilder
            ->setFirstResult($firstResult)
            ->setMaxResults($this->pageSize)
            ->getQuery();

        if (0 === count($this->queryBuilder->getDQLPart('join'))) {
            $query->setHint(CountWalker::HINT_DISTINCT, false);
        }

        $paginator = new DoctrinePaginator($query, true);

        $useOutputWalkers = count($this->queryBuilder->getDQLPart('having') ?: []) > 0;
        $paginator->setUseOutputWalkers($useOutputWalkers);

        $this->results = $paginator->getIterator();
        $this->numResults = $paginator->count();

        return $this;
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function getLastPage(): int
    {
        return (int) ceil($this->numResults / $this->pageSize);
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    public function hasPreviousPage(): bool
    {
        return $this->currentPage > 1;
    }

    public function getPreviousPage(): int
    {
        return max(1, $this->currentPage - 1);
    }

    public function getStartPage(): int
    {
        return $this->startPage;
    }

    public function getEndPage(): int
    {
        return $this->endPage;
    }

    public function hasNextPage(): bool
    {
        return $this->currentPage < $this->getLastPage();
    }

    public function getNextPage(): int
    {
        return min($this->getLastPage(), $this->currentPage + 1);
    }

    public function hasToPaginate(): bool
    {
        return $this->numResults > $this->pageSize;
    }

    public function getNumResults(): int
    {
        return $this->numResults;
    }

    public function getNumberOfPages(): int
    {
        $numberOfPages = $this->calculateNumberOfPages();

        if (0 === $numberOfPages) {
            return 1;
        }

        return $numberOfPages;
    }

    private function calculateNumberOfPages(): int
    {
        return (int) ceil($this->getNumResults() / self::PAGE_SIZE);
    }

    public function getResults(): Traversable
    {
        return $this->results;
    }

    public function count(): int
    {
        return count($this->getResults());
    }

    /** @return ArrayIterator<int, object> */
    public function getIterator(): Traversable
    {
        $results = $this->getResults();

        if ($results instanceof Iterator) {
            return $results;
        }

        if ($results instanceof IteratorAggregate) {
            return $results->getIterator();
        }

        return new ArrayIterator($results);
    }

    public function calculateStartAndEndPage(): void
    {
        $startPage = $this->currentPage - 3;
        $endPage = $this->currentPage + 3;

        if ($this->startPageUnderflow($startPage)) {
            $endPage = $this->calculateEndPageForStartPageUnderflow($startPage, $endPage);
            $startPage = 1;
        }

        if ($this->endPageOverflow($endPage)) {
            $startPage = $this->calculateStartPageForEndPageOverflow($startPage, $endPage);
            $endPage = $this->getNumberOfPages();
        }

        $this->startPage = $startPage;
        $this->endPage = $endPage;
    }

    private function startPageUnderflow($startPage): bool
    {
        return $startPage < 1;
    }

    private function endPageOverflow($endPage): bool
    {
        return $endPage > $this->getNumberOfPages();
    }

    private function calculateEndPageForStartPageUnderflow($startPage, $endPage): int
    {
        return min($endPage + (1 - $startPage), $this->getNumberOfPages());
    }

    private function calculateStartPageForEndPageOverflow($startPage, $endPage): int
    {
        return max($startPage - ($endPage - $this->getNumberOfPages()), 1);
    }
}
