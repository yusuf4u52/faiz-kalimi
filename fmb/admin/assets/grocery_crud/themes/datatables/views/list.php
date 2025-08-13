<div class="table-responsive">
	<table cellpadding="0" cellspacing="0" border="0" class="display groceryCrudTable" id="<?php echo uniqid(); ?>">
		<thead>
			<tr>
				<th class='actions'><?php echo $this->l('list_actions'); ?></th>
				<?php foreach($columns as $column){?>
					<th><?php echo $column->display_as; ?></th>
				<?php }?>
				<?php if(!$unset_delete || !$unset_edit || !$unset_read || !empty($actions)){?>
				<?php }?>
			</tr>
		</thead>
		<tbody>
			<?php foreach($list as $num_row => $row){ ?>
			<tr id='row-<?php echo $num_row?>'>
				<?php if(!$unset_delete || !$unset_edit || !$unset_read || !empty($actions)){?>
				<td class='actions'>
					<?php
					if(!empty($row->action_urls)){
						foreach($row->action_urls as $action_unique_id => $action_url){
							$action = $actions[$action_unique_id];
					?>
							<a href="<?php echo $action_url; ?>" class="edit_button ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary" role="button">
								<span class="ui-button-icon-primary ui-icon <?php echo $action->css_class; ?> <?php echo $action_unique_id;?>"></span><span class="ui-button-text">&nbsp;<?php echo $action->label?></span>
							</a>
					<?php }
					}
					?>
					<?php if(!$unset_read){?>
						<a href="<?php echo $row->read_url?>" class="edit_button ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary btn" role="button">
							<i class="bi bi-eye"></i>
						</a>
					<?php }?>

					<?php if(!$unset_clone){?>
						<a href="<?php echo $row->clone_url?>" class="edit_button ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary btn" role="button">
							<i class="bi bi-copy"></i>
						</a>
					<?php }?>

					<?php if(!$unset_edit){?>
						<a href="<?php echo $row->edit_url?>" class="edit_button ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary btn" role="button">
							<i class="bi bi-pencil-square"></i>
						</a>
					<?php }?>

					<?php if(!$unset_delete){?>
						<a onclick = "javascript: return delete_row('<?php echo $row->delete_url?>', '<?php echo $num_row?>')"
							href="javascript:void(0)" class="delete_button ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary btn" role="button">
							<i class="bi bi-trash"></i>
						</a>
					<?php }?>
				</td>
				<?php }?>
				<?php foreach($columns as $column){?>
					<td><?php echo $row->{$column->field_name}?></td>
				<?php }?>
			</tr>
			<?php }?>
		</tbody>
		<tfoot>
			<tr>
				<?php if(!$unset_delete || !$unset_edit || !$unset_read || !empty($actions)){?>
					<th>
						<a href="javascript:void(0)" class="refresh-data ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary btn" role="button" data-url="<?php echo $ajax_list_url; ?>">
							<i class="bi bi-arrow-repeat"></i>
						</a>
						<a href="javascript:void(0)" role="button" class="clear-filtering ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary btn">
							<i class="bi bi-arrow-clockwise"></i>
						</a>
					</th>
				<?php }?>
				<?php foreach($columns as $column){?>
					<th><input type="text" name="<?php echo $column->field_name; ?>" placeholder="<?php echo $this->l('list_search').' '.$column->display_as; ?>" class="search_<?php echo $column->field_name; ?>" /></th>
				<?php }?>
			</tr>
		</tfoot>
	</table>
</div>