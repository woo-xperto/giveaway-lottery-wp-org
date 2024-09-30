<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 
// wp admin Dashboard Left side menu page for Giveaway Setting
function wxgiveaway_setting_page() {
    echo '<h1>' . esc_html__('Giveaways Settings', 'giveaway-lottery') . '</h1><hr>';
    if (defined("WXGIVEAWAY_SETTINGS")) {
        $settings = WXGIVEAWAY_SETTINGS;
    } else {
        $settings = get_option('wxgiveaway_settings');
    }
    ?>
    <div class="gift-card-setting-wrap">
        <form action="options.php" method="post">
            <?php wp_nonce_field('update-options'); ?>
            <table>
                <tr>
                    <th><?php echo esc_html__('When tickets will be generated?', 'giveaway-lottery'); ?></th>
                    <td>
                        <select name="wxgiveaway_settings[ticket_generate]">
                            <option value="processing" <?php selected($settings['ticket_generate'], 'processing'); ?>><?php echo esc_html__('Order status on processing', 'giveaway-lottery'); ?></option>
                            <option value="completed" <?php selected($settings['ticket_generate'], 'completed'); ?>><?php echo esc_html__('Order status on completed', 'giveaway-lottery'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php echo esc_html__('Delete generated tickets if the order status is', 'giveaway-lottery'); ?></th>
                    <td>
                        <select name="wxgiveaway_settings[ticket_delete_at]">
                            <option value="refunded" <?php selected($settings['ticket_delete_at'], 'refunded'); ?>><?php echo esc_html__('Refunded', 'giveaway-lottery'); ?></option>
                            <option value="cancelled" <?php selected($settings['ticket_delete_at'], 'cancelled'); ?>><?php echo esc_html__('Canceled', 'giveaway-lottery'); ?></option>
                            <option value="checkout-draft" <?php selected($settings['ticket_delete_at'], 'checkout-draft'); ?>><?php echo esc_html__('Draft', 'giveaway-lottery'); ?></option>
                            <option value="failed" <?php selected($settings['ticket_delete_at'], 'failed'); ?>><?php echo esc_html__('Failed', 'giveaway-lottery'); ?></option>
                            <option value="on-hold" <?php selected($settings['ticket_delete_at'], 'on-hold'); ?>><?php echo esc_html__('On hold', 'giveaway-lottery'); ?></option>
                            <option value="pending" <?php selected($settings['ticket_delete_at'], 'pending'); ?>><?php echo esc_html__('Pending payment', 'giveaway-lottery'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php echo esc_html__('Send tickets by email?', 'giveaway-lottery'); ?></th>
                    <td>
                        <?php
                        $ticket_send = (isset($settings['ticket_send']) ? $settings['ticket_send'] : '');
                        ?>
                        <input type="checkbox" name="wxgiveaway_settings[ticket_send]" <?php checked($ticket_send); ?>>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Ticket style', 'giveaway-lottery'); ?></th>
                    <td>
                        <select name="wxgiveaway_settings[ticket_style]">
                            <option value="style1" <?php selected($settings['ticket_style'], 'style1'); ?>><?php echo esc_html__('Style 1', 'giveaway-lottery'); ?></option>
                            <?php
                            do_action('wxgiveaway_admin_ticket_style_options'); // add more ticket styles in settings
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Logo URL', 'giveaway-lottery'); ?></th>
                    <td>
                        <input type="text" name="wxgiveaway_settings[logo_url]" value="<?php echo esc_attr($settings['logo_url']); ?>">
                    </td>
                </tr>
                <?php
                do_action('wxgiveaway_admin_setting_options', $settings); // add more settings elements
                ?>
                <tr>
                    <td>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="page_options" value="wxgiveaway_settings">
                        <input class="button button-primary button-large" type="submit" name="submit" value="<?php esc_attr_e('Save Settings', 'giveaway-lottery'); ?>">
                    </td>
                </tr>
            </table>
        </form>
    </div>
    <?php
    
}
?>
