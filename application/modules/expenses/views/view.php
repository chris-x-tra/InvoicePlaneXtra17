<?php
  $this->load->helper('custom_values_helper');	// <- format_date
?>
<div id="headerbar">
	<h1 class="headerbar-title">
		<?php _trans('view_expense'); ?>
	</h1>
	<div class="headerbar-item pull-right">
		<div class="btn-group btn-group-sm">

            <a href="<?php echo site_url('expenses/status/all'); ?>"
               class="btn btn-default">
                <?php _trans('view_all'); ?>
            </a>
			<a href="<?php echo site_url('expenses/form/' . $expense->expense_id); ?>" class="btn btn-default">
				<i class="fa fa-edit"></i> <?php _trans('edit'); ?>
			</a>
		</div>
	</div>
</div>

<?php /*
'expense_id',			// 0			<- primary key
'expense_number',		// XTD-B-0001		// my number number in the ip program - ??? not used atm - rethink
'expense_description',		// Netzteil schnell besorgt wegen notfall // my personal desription
'expense_status_id',		// paid, ???
'expense_category_id',		// ? <- buchhaltungskategorie for later use
'expense_supplier_id',		// 1			// id in clients table
'expense_date',			// 01.07.2024		// date on suppliere expense
'expense_due_date',		// 14.07.2024	// not needed!
'expense_paid_date',		// 18.07.2024	// not needed!
'expense_amount',		// 39.95 [EUR]
'expense_bank_book_day',	// 19.07.2024
'expense_bank_book_subject',	// Zahlung an ARLT
'expense_date_created',		// 01.07.2024	<- auto fields erstellt
'expense_date_modified'		// 19.07.2024	<- auto fields modified
*/ ?>


<style>
  .preview-wrapper {
    display: flex;
    align-items: flex-start; /* optional, top-align */
    gap: 1rem;               /* Abstand zwischen divs */
  }

  .preview-text {
    flex: 1;

min-width:50%;
  max-width: 60%;
  flex-shrink: 1;
  flex-grow: 0;
  }

  .preview-image {
    max-width: 40%;
  }


.document-box {
    text-align: left;
    border: 1px solid #ddd;
    padding: 1rem;
    margin-bottom: 1rem;
    background-color: #fafafa;
}

.document-preview-img {
    max-width: 100%;
    height: auto;
    border-radius: 4px;
    margin-bottom: 0.5rem;
}

.document-caption {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.5rem;
}

.document-filename {
    flex: 1;
    text-align: left;
    font-weight: 500;
    word-break: break-word;
}

.delete-button {
    padding: 0.3rem 0.5rem;
}

</style>

<div id="content" class="preview-wrapper">
		<div class="preview-text" >

			<div class="panel-heading"><?php _trans('expense'); ?></div>

			<table class="table no-margin">

				<tr>
					<th><?php _trans('expense_id'); ?></th>
					<td><?php _htmlsc($expense->expense_id); ?></td>
				</tr>
				<tr>
					<th><?php _trans('expense_number'); ?></th>
					<td><?php _htmlsc($expense->expense_number); ?></td>
				</tr>
				<tr>
					<th><?php _trans('expense_description'); ?></th>
					<td><?php _htmlsc($expense->expense_description); ?></td>
				</tr>
				<tr>
					<th><?php _trans('supplier'); ?></th>
					<td>
			        <a href="<?php echo site_url('clients/view/' . $expense->expense_supplier_id); ?>" >
					<?php _htmlsc($expense->client_name); ?>
					<?php _htmlsc($expense->client_surname); ?>
                    </a>
                    (ID: <?php _htmlsc($expense->expense_supplier_id); ?>)
                    </td>
				</tr>
				<tr>
					<th><?php _trans('expense_amount'); ?></th>
					<td><?php echo format_currency($expense->expense_amount); ?></td>
				</tr>
				<tr>
					<th><?php _trans('expense_status'); ?></th>
					<td><?php _htmlsc($expense->expense_status_id); ?></td>
				</tr>
				<tr>
					<th><?php _trans('expense_category'); ?></th>
                    <td><?php if($expense->expense_category_id) echo $expense_types[$expense->expense_category_id]; ?></td>
				</tr>
				<tr>
					<th><?php _trans('expense_date'); ?></th>
					<td><?php echo format_date($expense->expense_date); ?></td>
				</tr>
<!--
				<tr>
					<th><?php _trans('expense_due_date'); ?></th>
					<td><?php echo format_date($expense->expense_due_date); ?></td>
				</tr>
				<tr>
					<th><?php _trans('expense_paid_date'); ?></th>
					<td><?php echo format_date($expense->expense_paid_date); ?></td>
				</tr>
-->
				<tr>
					<th><?php _trans('expense_bank_book_date'); ?></th>
					<td><?php echo format_date($expense->expense_bank_book_date); ?></td>
				</tr>
				<tr>
					<th><?php _trans('expense_bank_book_subject'); ?></th>
					<td><?php _htmlsc($expense->expense_bank_book_subject); ?></td>
				</tr>

				</table>
</div>

<div class="preview-image" >
<!-- expenses images-->
    <?php _trans('expenses_documents'); ?>
    <br>
    <?php
    if (!empty($expenses_documents)): 
        foreach ($expenses_documents as $d): ?>
    <div class="document-box">
        <a href="/uploads/expenses_documents/<?php echo ($d->document_filename); ?>" target="_blank">
        <?php $preview_path = base_url('uploads/expenses_documents/previews/' . pathinfo($d->document_filename, PATHINFO_FILENAME) . '_preview.jpg');
        echo '<img src="' . $preview_path . '" style="max-width: 200px">';
        ?>
    <div class="document-caption">
        <?php 
        // Zerlege den String an jedem _
        $parts = explode('_', $d->document_filename);
        // Entferne die ersten 3 Teile
        $remaining = array_slice($parts, 3);
        // Füge den Rest wieder mit _ zusammen
        $clean_filename = implode('_', $remaining);
        ?>
        <span class="document-filename"><?php echo $clean_filename; ?></span>
        </a>

        <form action="<?php echo site_url('expenses/delete_document/'.$expense->expense_id . '/' . $d->document_id); ?>"
            method="POST">
          <?php _csrf_field(); ?>
              <button type="submit" class="button delete-button"
                      onclick="return confirm('<?= trans('delete_record_warning') ?>');">
                  <i class="fa fa-trash-o fa-margin"></i> <?php _trans('delete'); ?>
              </button>
        </form>
    </div>
    </div>
    <?php 
    endforeach;
    endif; ?>
<!-- //END expenses images-->
</div>

</div>
</div>

