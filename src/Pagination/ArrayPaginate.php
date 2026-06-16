<?php

namespace JiJiHoHoCoCo\IchiORM\Pagination;

class ArrayPaginate
{
    public function paginate(array $dataArray, int $perPage = 10)
    {
        $paginate = new Paginate();
        $paginate->setPaginateData($perPage);
        $start = $paginate->getStart();
        $objectArray = [];
        $dataArray = array_values($dataArray);

        foreach (range($start + 1, $start + $perPage) as $value) {
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
