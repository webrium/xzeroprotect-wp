<?php defined('ABSPATH') || exit;
$xzp_type_labels = [
    'rate_limit'   => __('Rate Limit', 'xzeroprotect-wp'),
    'banned_ip'    => __('Banned IP', 'xzeroprotect-wp'),
    'blocked_path' => __('Blocked Path', 'xzeroprotect-wp'),
    'user_agent'   => __('Bad User-Agent', 'xzeroprotect-wp'),
    'payload'      => __('Payload Attack', 'xzeroprotect-wp'),
    'custom_rule'  => __('Custom Rule', 'xzeroprotect-wp'),
];
?>
<div class="xzp-wrap">

    <div class="xzp-header">
        <div class="xzp-header__left">
            <span class="xzp-logo">🚫</span>
            <div>
                <h1><?php esc_html_e('Blocked Requests', 'xzeroprotect-wp'); ?></h1>
                <p><?php esc_html_e('Bots, scanners, and attack attempts intercepted by the firewall', 'xzeroprotect-wp'); ?></p>
            </div>
        </div>
    </div>

    <div class="xzp-panel">
        <div class="xzp-panel__body xzp-panel__body--flush">
            <?php if (empty($blocks)): ?>
                <p class="xzp-empty"><?php esc_html_e("No blocked requests yet. That's a good sign!", 'xzeroprotect-wp'); ?></p>
            <?php else: ?>
            <table class="xzp-table xzp-table--full">
                <thead><tr>
                    <th><?php esc_html_e('Time', 'xzeroprotect-wp'); ?></th>
                    <th><?php esc_html_e('IP', 'xzeroprotect-wp'); ?></th>
                    <th><?php esc_html_e('URI', 'xzeroprotect-wp'); ?></th>
                    <th><?php esc_html_e('Type', 'xzeroprotect-wp'); ?></th>
                    <th><?php esc_html_e('Reason', 'xzeroprotect-wp'); ?></th>
                    <th><?php esc_html_e('User-Agent', 'xzeroprotect-wp'); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ($blocks as $xzp_b): ?>
                    <tr>
                        <td class="xzp-td-time"><?php echo esc_html($xzp_b['blocked_at']); ?></td>
                        <td><code><?php echo esc_html($xzp_b['ip']); ?></code></td>
                        <td class="xzp-td-path" title="<?php echo esc_attr($xzp_b['uri']); ?>"><?php echo esc_html($xzp_b['uri']); ?></td>
                        <td>
                            <span class="xzp-badge xzp-badge--block">
                                <?php echo esc_html($xzp_type_labels[$xzp_b['block_type']] ?? $xzp_b['block_type']); ?>
                            </span>
                        </td>
                        <td class="xzp-td-reason"><?php echo esc_html($xzp_b['reason']); ?></td>
                        <td class="xzp-td-ua" title="<?php echo esc_attr($xzp_b['user_agent']); ?>"><?php echo esc_html(substr($xzp_b['user_agent'], 0, 60)); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

</div>
