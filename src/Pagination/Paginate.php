<?php

namespace JiJiHoHoCoCo\IchiORM\Pagination;

class Paginate
{
    private $perPage;
    private $pageCheck;
    private $currentPage;
    private $start;

    public function setPaginateData(int $perPage)
    {
        $this->perPage = $perPage;
        $this->pageCheck = pageCheck();
        $this->currentPage = $this->pageCheck ? intval($_GET['page']) : 1;
        $this->start = ($this->currentPage > 1) ? ($this->perPage * ($this->currentPage - 1)) : 0;
    }

    public function getStart()
    {
        return $this->start;
    }

    public function paginate($total, $objectArray)
    {
        $totalPages = ceil($total / $this->perPage);
        $nextPage = $this->currentPage + 1;
        $previousPage = $this->pageCheck && $_GET['page'] - 1 >= 1 ? $_GET['page'] - 1 : null;
        $from = $this->start + 1;

        $domainName = getDomainName();
        $totalPerPage = count($objectArray);
        $to = ($from + $totalPerPage) - 1;

        return [
            'current_page' => $this->currentPage,
            'data' => $objectArray,
            'first_page_url' => makePaginateLink($domainName, '1'),
            'from' => $from > $totalPages ? null : $from,
            'last_page' => $totalPages,
            'last_page_url' => makePaginateLink($domainName, $totalPages),
            'next_page_url' => $nextPage <= $totalPages ? makePaginateLink($domainName, $nextPage) : null,
            'path' => $domainName,
            'per_page' => $this->perPage,
            'prev_page_url' => $previousPage !== null ? makePaginateLink($domainName, $previousPage) : null,
            'to' => $to <= 0 || $to > $total ? null : $to,
            'total' => $totalPerPage
        ];
    }
}
