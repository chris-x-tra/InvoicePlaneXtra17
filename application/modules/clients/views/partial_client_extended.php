<table class="table no-margin">
        <tr>
                <th><?php _trans('client_type'); ?></th>
                <td>
                <?php echo $client_extended->client_type ?  $client_types[$client_extended->client_type] :  ''; ?>
                </td>
        </tr>
        <tr>
                <th><?php _trans('customer_no'); ?></th>
                <td><?php echo $client_extended->customer_no ? $client_extended->customer_no :  ''; ?></td>
        </tr>
            <tr>
                <th><?php _trans('carelevel'); ?></th>
                <td><?php echo $client_extended->carelevel ? $client_extended->carelevel :  ''; ?></td>
            </tr>
            <tr>
                <th><?php _trans('carelevel_since'); ?></th>
                <td><?php echo $client_extended->carelevel_since ? format_date($client_extended->carelevel_since) :  ''; ?></td>
            </tr>
            <tr>
                <th><?php _trans('health_insurance_number'); ?></th>
                <td><?php echo $client_extended->health_insurance_number ? $client_extended->health_insurance_number :  ''; ?></td>
            </tr>
        <tr>
                <th><?php _trans('client_flags'); ?></th>
                <td>
                <?= customer_satisfaction_smileys($client_extended->client_flags); ?>
                </td>
        </tr>
            <tr>
                <th><?php _trans('contract'); ?></th>
                <td><?php echo $client_extended->contract ? $client_extended->contract :  ''; ?></td>
            </tr>

            <tr>
                <th><?php _trans('memo'); ?></th>
                <td><?php echo $client_extended->memo ? $client_extended->memo :  ''; ?></td>
            </tr>
</table>
