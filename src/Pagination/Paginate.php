<?php

namespace JiJiHoHoCoCo\IchiORM\Pagination;

class Paginate
{

    private $per_page, $pageCheck, $current_page, $start;

	public function setPaginateData(int $per_page)
	{
		$this->per_page = $per_page;
		$this->pageCheck = pageCheck();
		$this->current_page = $this->pageCheck ? intval($_GET['page']) : 1;
		$this->start = ($this->current_page > 1) ? ($this->per_page * ($this->current_page - 1)) : 0;
	}

	public function getStart()
	{
		return $this->start;
	}

	public function paginate($total, $objectArray)
	{
		$total_pages = ceil($total / $this->per_page);
		$next_page = $this->current_page + 1;
		$previous_page = $this->pageCheck && $_GET['page'] - 1 >= 1 ? $_GET['page'] - 1 : NULL;
		$from = $this->start + 1;

		$domainName = getDomainName();
		$totalPerPage = count($objectArray);
		$to = ($from + $totalPerPage) - 1;

		return [
			'current_page' => $this->current_page,
			'data' => $objectArray,
			'first_page_url' => makePaginateLink($domainName, '1'),
			'from' => $from > $total_pages ? NULL : $from,
			'last_page' => $total_pages,
			'last_page_url' => makePaginateLink($domainName, $total_pages),
			'next_page_url' => $next_page <= $total_pages ? makePaginateLink($domainName, $next_page) : NULL,
			'path' => $domainName,
			'per_page' => $this->per_page,
			'prev_page_url' => $previous_page !== NULL ? makePaginateLink($domainName, $previous_page) : NULL,
			'to' => $to <= 0 || $to > $total ? NULL : $to,
			'total' => $totalPerPage
		];
	}
}