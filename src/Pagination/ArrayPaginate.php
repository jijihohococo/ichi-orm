<?php

namespace JiJiHoHoCoCo\IchiORM\Pagination;

class ArrayPaginate
{
    public function paginate(array $dataArray, int $per_page = 10)
    {
        $paginate = new Paginate();
        $paginate->setPaginateData($per_page);
        $start = $paginate->getStart();
        $objectArray = [];
        $dataArray = array_values($dataArray);

        foreach (range($start + 1, $start + $per_page) as $key => $value) {
            // code...
            if (isset($dataArray[$value - 1])) {
                $objectArray[] = $dataArray[$value - 1];
            }
        }
        return $paginate->paginate(
            count($dataArray),
            $objectArray
        );
    }
}
