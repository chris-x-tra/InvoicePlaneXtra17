
<!-- clients/views/partial_notes.php -->

<style>
#client-notes-container .panel {
    display: none;
}
</style>

<div id="client-notes-container">
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
?>
</div>

<?php if (ip_mari()): ?>
<button id="show-more-notes" class="btn btn-default btn-sm">
    Read more
</button>
<br>
<br>
<script>
// first show three
$(document).ready(function() {
    var notes = $('#client-notes-container .panel');
    var limit = 3;

    notes.hide().slice(0, limit).show();
});
// then toggle, TODO php ajax stuff but not yet
$(document).ready(function() {
    var notes = $('#client-notes-container .panel');
    var expanded = false;

    notes.slice(3).hide();

    $('#show-more-notes').on('click', function() {
        if (!expanded) {
            notes.slideDown();
            $(this).text('Read less');
        } else {
            notes.slice(3).slideUp();
            $(this).text('Read more');
        }
        expanded = !expanded;
    });
});
</script>
<?php endif; ?>
