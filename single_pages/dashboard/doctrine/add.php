<div class="ccm-ui">
	<div class="row">
		<div class="">
			<div class="ccm-pane">
				<div class="ccm-pane-header">
					<div style="display: none" id="ccm-page-navigate-pages-content">
					</div>
					<ul class="ccm-pane-header-icons">
					
					</ul>

					<h3><?php echo t('Add Post') ?></h3>
				</div>	
				<div class="ccm-pane-options">

					<a class="btn  ccm-button-v2-left" href="<?php echo $this->action('add_post') ?>"><?php echo t('Add post') ?></a>

					<a class="btn  ccm-button-v2-left" href="<?php echo $this->action('view') ?>"><?php echo t('List posts') ?></a>									

				</div>

				<div class="ccm-pane-body">
					<form method="post" action="<?php echo $this->action('add_post') ?>">
<table cellspacing="0" cellpadding="0" border="0" class="ccm-results-list" >
	<tbody>
		
		<tr>
			<td>
				<?php echo t('Title') ?>
			</td>
			<td>
				<input type="text" name="title" value="" />
			</td>
		</tr>
		<tr>
			<td>
				<?php echo t('Body') ?>
			</td>
			<td>
				<textarea name="body">
					
				</textarea>
			</td>
		</tr>
			<tr>
				<td>
				
				</td>
				<td>
					<input type="submit" value="Save" />
				</td>
			</tr>

	</tbody>
</table>
				</form>
				</div>


			</div>

		</div>
	</div>



</div>


