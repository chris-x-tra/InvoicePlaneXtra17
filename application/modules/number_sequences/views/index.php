<div id="headerbar">
    <h1 class="headerbar-title"><?php _trans('number_sequences'); ?></h1>

<!--
User shall not simply add new sequences in this case
    <div class="headerbar-item pull-right">
        <a class="btn btn-sm btn-primary" href="<?php echo site_url('number_sequences/form'); ?>">
            <i class="fa fa-plus"></i> <?php _trans('new'); ?>
        </a>
    </div>
-->

    <div class="headerbar-item pull-right">
        <?php echo pager(site_url('number_sequences/index'), 'mdl_number_sequences'); ?>
    </div>

</div>

<div id="content" class="table-content">

    <?php $this->layout->load_view('layout/alerts'); ?>

    <div class="table-responsive">
        <table class="table table-hover table-striped">

            <thead>
            <tr>
                <th><?php _trans('name'); ?></th>
                <th><?php _trans('next_id'); ?></th>
                <th><?php _trans('left_pad'); ?></th>
                <th><?php _trans('options'); ?></th>
            </tr>
            </thead>

            <tbody>
            <?php foreach ($number_sequences as $number_sequence) { ?>
                <tr>
                    <td><?php _htmlsc($number_sequence->number_sequence_name); ?></td>
                    <td><?php echo $number_sequence->number_sequence_next_id; ?></td>
                    <td><?php echo $number_sequence->number_sequence_left_pad; ?></td>
                    <td>
                        <div class="options btn-group">
                            <a href="<?php echo site_url('number_sequences/form/' . $number_sequence->number_sequence_id); ?>">
                                <i class="fa fa-edit fa-margin"></i> <?php _trans('edit'); ?>
                            </a>
                        </div>
                    </td>
                </tr>
            <?php } ?>
            </tbody>

        </table>
    </div>

</div>
