<?php
/*
 * Generische Dokumentenliste.
 * Benötigt: $documents, $owner_type ('client'|'user'), $owner_id
 * Einbinden: <?php $this->load->view('documents/partial_document_table', [...]); ?>
 */
?>
<p>
    <a href="<?php echo site_url('documents/upload/' . $owner_type . '/' . $owner_id); ?>" class="btn btn-sm btn-default">
        <i class="fa fa-plus"></i> <?php _trans('upload_document'); ?>
    </a>
</p>

<div class="table-responsive">
    <table class="table table-hover table-striped">
        <thead>
        <tr>
            <th>ID</th>
            <th style="width:64px"></th>
            <th><?php _trans('filename'); ?></th>
            <th><?php _trans('description'); ?></th>
            <th><?php _trans('creation_date'); ?></th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($documents as $d) : ?>
            <tr>
                <td><?php echo (int) $d->document_id; ?></td>
                <td>
                    <?php if (preg_match('/\.(jpe?g|png|gif|webp|pdf)$/i', $d->document_filename)) : ?>
                        <a href="<?php echo site_url('documents/download/' . $d->document_id); ?>">
                            <img src="<?php echo site_url('documents/preview/' . $d->document_id); ?>" alt=""
                                 loading="lazy" style="max-width:56px;max-height:56px;border:1px solid #ddd;background:#fff"
                                 onerror="this.style.display='none'">
                        </a>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="<?php echo site_url('documents/download/' . $d->document_id); ?>">
                        <?php echo htmlspecialchars(preg_replace('/^[A-Z]\d{4}_[a-f0-9]{6}_/', '', $d->document_filename)); ?>
                    </a>
                </td>
                <td><?php echo htmlspecialchars($d->document_description ?? ''); ?></td>
                <td><?php echo (new DateTime($d->document_created))->format('d.m.Y'); ?></td>
                <td>
                    <form method="post" action="<?php echo site_url('documents/delete/' . $d->document_id); ?>"
                          onsubmit="return confirm('<?php echo addslashes(trans('delete_attachment_warning')); ?>');" style="display:inline">
                        <input type="hidden" name="<?php echo $this->config->item('csrf_token_name'); ?>"
                               value="<?php echo $this->security->get_csrf_hash(); ?>">
                        <button type="submit" class="btn btn-default"><?php _trans('delete'); ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
