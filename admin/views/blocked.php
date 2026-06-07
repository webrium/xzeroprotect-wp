<?php defined('ABSPATH') || exit;
$typeLabels = [
    'rate_limit'   => __('Rate Limit', 'xzeroprotect'),
    'banned_ip'    => __('Banned IP', 'xzeroprotect'),
    'blocked_path' => __('Blocked Path', 'xzeroprotect'),
    'user_agent'   => __('Bad User-Agent', 'xzeroprotect'),
    'payload'      => __('Payload Attack', 'xzeroprotect'),
    'custom_rule'  => __('Custom Rule', 'xzeroprotect'),
];
?>
<div class="xzp-wrap">

    <div class="xzp-header">
        <div class="xzp-header__left">
            <span class="xzp-logo">🚫</span>
            <div>
                <h1><?php _e('Blocked Requests', 'xzeroprotect'); ?></h1>
                <p><?php _e('Bots, scanners, and attack attempts intercepted by the firewall', 'xzeroprotect'); ?></p>
            </div>
        </div>
    </div>

    <div class="xzp-panel">
        <div class="xzp-panel__body xzp-panel__body--flush">
            <?php if (empty($blocks)): ?>
                <p class="xzp-empty"><?php _e('No blocked requests yet. That\'s a good sign!', 'xzeroprotect'); ?></p>
            <?php else: ?>
            <table class="xzp-table xzp-table--full">
                <thead><tr>
                    <th><?php _e('Time', 'xzeroprotect'); ?></th>
                    <th><?php _e('IP', 'xzeroprotect'); ?></th>
                    <th><?php _e('URI', 'xzeroprotect'); ?></th>
                    <th><?php _e('Type', 'xzeroprotect'); ?></th>
                    <th><?php _e('Reason', 'xzeroprotect'); ?></th>
                    <th><?php _e('User-Agent', 'xzeroprotect'); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ($blocks as $b): ?>
                    <tr>
                        <td class="xzp-td-time"><?php echo esc_html($b['blocked_at']); ?></td>
                        <td><code><?php echo esc_html($b['ip']); ?></code></td>
                        <td class="xzp-td-path" title="<?php echo esc_attr($b['uri']); ?>"><?php echo esc_html($b['uri']); ?></td>
                        <td>
                            <span class="xzp-badge xzp-badge--block">
                                <?php echo esc_html($typeLabels[$b['block_type']] ?? $b['block_type']); ?>
                            </span>
                        </td>
                        <td class="xzp-td-reason"><?php echo esc_html($b['reason']); ?></td>
                        <td class="xzp-td-ua" title="<?php echo esc_attr($b['user_agent']); ?>"><?php echo esc_html(substr($b['user_agent'], 0, 60)); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

</div>
