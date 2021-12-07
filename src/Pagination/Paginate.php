<?php

namespace JiJiHoHoCoCo\IchiORM\Pagination;

class Paginate{

	public function paginate(array $arrayData,int $per_page=10){
		

		$domainName=getDomainName();
		return [
			'current_page' => '',
			'data' => '',
			'first_page_url' => makePaginateLink($domainName,'1'),
			'from' => '',
			'last_page' => '',
			'last_page_url' => '',
			'next_page_url' => '',
			'path' => $domainName ,
			'per_page' => $per_page,
			'prev_page_url' => '',
			'to' => '',
			'total' => ''
		];
	}
}