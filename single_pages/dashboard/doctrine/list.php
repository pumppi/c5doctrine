<div class="ccm-ui">
	<div class="row">
		<div class="">
			<div class="ccm-pane">
				<div class="ccm-pane-header">
					<div style="display: none" id="ccm-page-navigate-pages-content">
					</div>
					<ul class="ccm-pane-header-icons">
					
					</ul>

					<h3><?php echo t('Posts') ?></h3>
				</div>	
				<div class="ccm-pane-options">

					<a class="btn  ccm-button-v2-left" href="<?php echo $this->action('add_post') ?>"><?php echo t('Add post') ?></a>

					<a class="btn  ccm-button-v2-left" href="<?php echo $this->action('view') ?>"><?php echo t('List posts') ?></a>									

				</div>

				<div class="ccm-pane-body">
<table cellspacing="0" cellpadding="0" border="0" class="ccm-results-list" >
	<tbody>
		<tr>
			<th><?php echo t('Title') ?></th>
			<th><?php echo t('Body') ?></th>
		</tr>
		<?php foreach($posts as $obj): ?>
		<tr>
			<td>
				<?php echo $obj->getTitle() ?>
			</td>
			<td>
				<?php echo $obj->getBody() ?>
			</td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>
				</div>


			</div>

		</div>
	</div>



</div>


