<?php defined('ABSPATH') || exit; ?>
<div class="xzp-wrap">

    <div class="xzp-header">
        <div class="xzp-header__left">
            <span class="xzp-logo">👤</span>
            <div>
                <h1><?php _e('Real Visitors', 'xzeroprotect'); ?></h1>
                <p><?php _e('Verified human traffic — bots and scanners excluded', 'xzeroprotect'); ?></p>
            </div>
        </div>
    </div>

    <div class="xzp-panel">
        <div class="xzp-panel__body xzp-panel__body--flush">
            <?php if (empty($visits)): ?>
                <p class="xzp-empty"><?php _e('No visitor data yet. Make sure Visitor Tracking is enabled in Settings.', 'xzeroprotect'); ?></p>
            <?php else: ?>
            <table class="xzp-table xzp-table--full">
                <thead><tr>
                    <th><?php _e('Time', 'xzeroprotect'); ?></th>
                    <th><?php _e('IP', 'xzeroprotect'); ?></th>
                    <th><?php _e('Path', 'xzeroprotect'); ?></th>
                    <th><?php _e('Browser', 'xzeroprotect'); ?></th>
                    <th><?php _e('OS', 'xzeroprotect'); ?></th>
                    <th><?php _e('Device', 'xzeroprotect'); ?></th>
                    <th><?php _e('Referer', 'xzeroprotect'); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ($visits as $v): ?>
                    <tr>
                        <td class="xzp-td-time"><?php echo esc_html($v['visited_at']); ?></td>
                        <td><code><?php echo esc_html($v['ip']); ?></code></td>
                        <td class="xzp-td-path" title="<?php echo esc_attr($v['path']); ?>"><?php echo esc_html($v['path']); ?></td>
                        <td><?php echo esc_html($v['browser'] . ' ' . $v['browser_ver']); ?></td>
                        <td><?php echo esc_html($v['os'] . ' ' . $v['os_ver']); ?></td>
                        <td>
                            <span class="xzp-badge xzp-badge--<?php echo esc_attr($v['device_type']); ?>">
                                <?php echo $v['device_type'] === 'mobile' ? '📱' : ($v['device_type'] === 'tablet' ? '📟' : '🖥'); ?>
                                <?php echo esc_html(ucfirst($v['device_type'])); ?>
                            </span>
                        </td>
                        <td class="xzp-td-referer"><?php echo $v['referer'] ? esc_html($v['referer']) : '<span class="xzp-dim">—</span>'; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

</div>
