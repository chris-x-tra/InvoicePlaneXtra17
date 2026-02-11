
<!-- clients/views/partial_notes.php -->
<style>
summary{
margin: 5px;
}
summary:hover{
/*text-decoration:underline;*/
cursor: pointer;
}

details {
/*
  border: solid;
*/
  padding: 2px 6px;
  margin-bottom: 1em;
}

details:first-of-type summary::marker,
:is(::-webkit-details-marker) {
  content: "+ ";
  font-family: monospace;
  color: red;
  font-weight: bold;
}

details[open]:first-of-type summary::marker {
  content: "- ";
}

details:last-of-type summary {
  list-style: none;
  &::after {
    content: "+";
    color: white;
    background-color:  #ff66cc;
    border-radius: 1em;
    font-weight: bold;
    padding: 0 5px;
    margin-inline-start: 5px;
  }
  [open] &::after {
    content: "-";
  }
}
details:last-of-type summary::-webkit-details-marker {
  display: none;
}
</style>


<?php
foreach ($client_notes as $client_note) {
?>
    <div class="panel panel-default small" data-note-id="<?= $client_note->client_note_id ?>">
        <div class="panel-body note-text">
            <?php echo nl2br(htmlsc($client_note->client_note)); ?>
        </div>
        <div class="panel-footer text-muted">
            <?php echo date_from_mysql($client_note->client_note_date, true); ?>

            <span class="edit-note btn btn-xs btn-default pull-right">
                <i class="fa fa-edit"></i><?php _trans('edit'); ?>
            </span>
            <span data-id="<?php echo $client_note->client_note_id; ?>" class="delete-note pull-right btn btn-xs btn-danger">
                <i class="fa fa-trash-o"></i> <?php _trans('delete'); ?>
            </span>
        </div>
    </div>
<?php
}
