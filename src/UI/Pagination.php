<?php

namespace JiJiHoHoCoCo\IchiORM\UI;

class Pagination
{
    public function paginate(array $paginateData, string $color = '#008080')
    {
        $currentPage = $paginateData['current_page'];
        $lastPage = $paginateData['last_page'];
        $nextPage = $currentPage + 1;
        $prevPageUrl = $paginateData['prev_page_url'];
        $path = $paginateData['path'];
        ?>
        <style type="text/css">
            .ichi-pagination {
                display: inline;
                text-align: center;
            }

            .ichi-pagination a {
                float: left;
                color: <?php echo $color; ?>;
                padding: 8px 10px;
                background-color: white;
                border: 1px solid #ddd;
                font-family: 'Nunito', sans-serif;
                font-size: 13px;
                text-decoration: none;
            }

            .ichi-pagination a.ichi-active {
                color: white;
                background-color:
                    <?php echo $color; ?>
                ;
            }

            .ichi-pagination a.ichi-disabled {
                color: grey;
                background-color: white;
            }
        </style>
        <div class="ichi-pagination">
            <?php if ($currentPage > 1) : ?>
                <a aria-label="Previous" href="<?php echo $prevPageUrl ?>">
                    <span aria-hidden="true">&laquo;</span>
                    <span>Previous</span>
                </a>
            <?php endif; ?>
            <?php if ($lastPage > 10 && $currentPage >= 8) : ?>
                <?php foreach ([1, 2] as $n) : ?>
                    <a href="<?php echo makePaginateLink($path, $n); ?>">
                        <?php echo $n; ?>
                    </a>
                <?php endforeach; ?>
                <a class="ichi-disabled">...</a>
            <?php endif; ?>
            <?php if ($lastPage <= 10) : ?>
                <?php foreach (range(1, $lastPage) as $n) : ?>
                    <?php if ($n == $currentPage) : ?>
                        <a class="ichi-active">
                            <?php echo $n; ?>
                        </a>
                    <?php else : ?>
                        <a href="<?php echo makePaginateLink($path, $n); ?>">
                            <?php echo $n; ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
            <?php if ($currentPage < 8 && $lastPage > 10) : ?>
                <?php foreach (range(1, 10) as $n) : ?>
                    <?php if ($n == $currentPage) : ?>
                        <a class="ichi-active">
                            <?php echo $n; ?>
                        </a>
                    <?php else : ?>
                        <a href="<?php echo makePaginateLink($path, $n); ?>">
                            <?php echo $n; ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
            <?php if ($currentPage >= 8 && $lastPage > 10) : ?>
                <?php foreach ([3, 2, 1] as $n) : ?>
                    <a href="<?php echo makePaginateLink($path, $currentPage - $n); ?>">
                        <?php echo $currentPage - $n; ?>
                    </a>
                <?php endforeach; ?>
                <a class="ichi-active">
                    <?php echo $currentPage; ?>
                </a>
            <?php endif; ?>
            <?php if ($currentPage >= 8 && $currentPage + 3 < $lastPage - 1 && $lastPage > 10) : ?>
                <?php foreach ([1, 2, 3] as $n) : ?>
                    <a href="<?php echo makePaginateLink($path, $currentPage + $n); ?>">
                        <?php echo $currentPage + $n; ?>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
            <?php if ($currentPage + 2 == $lastPage - 2 && $lastPage > 10) : ?>
                <?php foreach ([1, 2] as $n) : ?>
                    <a href="<?php echo makePaginateLink($path, $currentPage + $n); ?>">
                        <?php echo $currentPage + $n; ?>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
            <?php if ((($nextPage == $lastPage - 2) || ($nextPage == $lastPage)) && $lastPage > 10) : ?>
                <a href="<?php echo makePaginateLink($path, $nextPage); ?>">
                    <?php echo $nextPage; ?>
                </a>
            <?php endif; ?>
            <?php if ($currentPage < $lastPage - 1 && $lastPage > 10) : ?>
                <?php if ($lastPage - 5 > $currentPage) : ?>
                    <a class="ichi-disabled">...</a>
                <?php endif; ?>
                <a href="<?php echo makePaginateLink($path, $lastPage - 1); ?>">
                    <?php echo $lastPage - 1; ?>
                </a>
                <a href="<?php echo makePaginateLink($path, $lastPage); ?>">
                    <?php echo $lastPage; ?>
                </a>
            <?php endif; ?>
            <?php if ($lastPage > 1 && $nextPage <= $lastPage) : ?>
                <a aria-label="Next" href="<?php echo makePaginateLink($path, $nextPage); ?>">
                    <span aria-hidden='true'>&raquo;</span>
                    <span>Next</span>
                </a>
            <?php endif; ?>
            <div>
            <?php
    }
}