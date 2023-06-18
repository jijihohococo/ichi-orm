<?php

namespace JiJiHoHoCoCo\IchiORM\UI;

class Pagination{

	public function paginate(array $paginateData,string $color='#008080'){
		?>
		<style type="text/css">
			.ichi-pagination{
				display: inline;
				text-align: center;
			}
			.ichi-pagination a{
				float: left;
				color: <?php echo $color; ?>;
				padding: 8px 10px;
				background-color: white;
				border: 1px solid #ddd;
				font-family: 'Nunito', sans-serif;
				font-size: 13;
				text-decoration: none;
			}
			.ichi-pagination a.ichi-active{
				color: white;
				background-color: <?php echo $color;?>;
			}
			.ichi-pagination a.ichi-disabled{
				color: grey;
				background-color: white;
			}
		</style>
		<div class="ichi-pagination">
			<?php if($paginateData['current_page']>1): ?>
				<a  aria-label="Previous" href="<?php echo $paginateData['prev_page_url'] ?>" >
					<span aria-hidden="true">&laquo;</span>
					<span >Previous</span>
				</a>
			<?php endif; ?>
			<?php if($paginateData['last_page']>10 && $paginateData['current_page']>=8): ?>
				<?php foreach([1,2] as $n): ?>
					<a 
					href="<?php echo makePaginateLink($paginateData['path'],$n); ?>" 
					><?php echo $n; ?></a>
				<?php endforeach; ?>
				<a class="ichi-disabled">...</a>
			<?php endif; ?>
			<?php if($paginateData['last_page']<=10): ?>
				<?php foreach(range(1,$paginateData['last_page']) as $n): ?>
					<?php if($n==$paginateData['current_page']): ?>
						<a class="ichi-active"><?php echo $n; ?></a>
					<?php else: ?>
						<a  href="<?php echo makePaginateLink($paginateData['path'],$n); ?>">
							<?php echo $n; ?>
						</a>
					<?php endif; ?>
				<?php endforeach; ?>
			<?php endif; ?>
			<?php if($paginateData['current_page']<8 && $paginateData['last_page']>10): ?>
				<?php foreach (range(1,10) as $n): ?>
					<?php if($n==$paginateData['current_page']): ?>
						<a class="ichi-active"><?php echo $n; ?></a>
					<?php else: ?>
						<a  href="<?php echo makePaginateLink($paginateData['path'],$n); ?>"><?php echo $n; ?></a>
					<?php endif; ?>
				<?php endforeach; ?> 
			<?php endif; ?>
			<?php if($paginateData['current_page']>=8 && $paginateData['last_page']>10 ): ?>
				<?php foreach([3,2,1] as $n): ?>
					<a  href="<?php echo makePaginateLink($paginateData['path'],$paginateData['current_page']-$n); ?>"><?php echo $paginateData['current_page']-$n; ?></a>
				<?php endforeach;?>
				<a class="ichi-active"><?php echo $paginateData['current_page']; ?></a>
			<?php endif; ?>
			<?php if($paginateData['current_page']>=8 && $paginateData['current_page']+3<$paginateData['last_page']-1 && $paginateData['last_page']>10 ): ?>
				<?php foreach([1,2,3] as $n): ?>
					<a   href="<?php echo makePaginateLink($paginateData['path'],$paginateData['current_page']+$n); ?>"><?php echo $paginateData['current_page']+$n; ?></a>
				<?php endforeach;?>
			<?php endif;?>
			<?php if($paginateData['current_page']+2==$paginateData['last_page']-2 && $paginateData['last_page']>10): ?>
				<?php foreach([1,2] as $n): ?>
					<a  href="<?php echo makePaginateLink($paginateData['path'],$paginateData['current_page']+$n); ?>"><?php echo $paginateData['current_page']+$n; ?></a>
				<?php endforeach;?>
			<?php endif;?>
			<?php if((($paginateData['current_page']+1==$paginateData['last_page']-2) || ($paginateData['current_page']+1==$paginateData['last_page'])) && $paginateData['last_page']>10 ): ?>
			<a  href="<?php echo makePaginateLink($paginateData['path'],$paginateData['current_page']+1); ?>"><?php echo $paginateData['current_page']+1; ?></a>
		<?php endif;?>
		<?php if($paginateData['current_page']<$paginateData['last_page']-1 && $paginateData['last_page']>10 ): ?>
			<?php if($paginateData['last_page']-5>$paginateData['current_page']): ?>
				<a class="ichi-disabled">...</a>
			<?php endif; ?>
			<a  href="<?php echo makePaginateLink($paginateData['path'],$paginateData['last_page']-1); ?>"><?php echo $paginateData['last_page']-1; ?></a>
			<a href="<?php echo makePaginateLink($paginateData['path'],$paginateData['last_page']); ?>"><?php echo $paginateData['last_page']; ?></a>
		<?php endif; ?>
		<?php if($paginateData['last_page']>1 && $paginateData['current_page']+1<=$paginateData['last_page']): ?>
			<a  aria-label="Next" href="<?php echo makePaginateLink($paginateData['path'],$paginateData['current_page']+1); ?>">
				<span aria-hidden='true'>&raquo;</span>
				<span >Next</span>
			</a>
		<?php endif; ?>
		<div>
			<?php	
		}

	}