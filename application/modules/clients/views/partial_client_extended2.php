<table class="table no-margin">
            <tr>
                <th><?php _trans('direct_debit'); ?></th>
                <td><?php echo $client_extended->direct_debit ? $client_extended->direct_debit :  ''; ?></td>
            </tr>
            <tr>
                <th><?php _trans('bank_name'); ?></th>
                <td><?php echo $client_extended->bank_name ? $client_extended->bank_name :  ''; ?></td>
            </tr>
            <tr>
                <th><?php _trans('bank_bic'); ?></th>
                <td><?php echo $client_extended->bank_bic ? $client_extended->bank_bic :  ''; ?></td>
            </tr>
            <tr>
                <th><?php _trans('bank_iban'); ?></th>
                <td><?php echo $client_extended->bank_iban ? $client_extended->bank_iban :  ''; ?></td>
            </tr>
</table>
