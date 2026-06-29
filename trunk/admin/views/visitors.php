<?php defined('ABSPATH') || exit; ?>
<div class="xzp-wrap">

    <div class="xzp-header">
        <div class="xzp-header__left">
            <span class="xzp-logo">👤</span>
            <div>
                <h1><?php esc_html_e('Real Visitors', 'xzeroprotect'); ?></h1>
                <p><?php esc_html_e('Verified human traffic — bots and scanners excluded', 'xzeroprotect'); ?></p>
            </div>
        </div>
    </div>

    <div class="xzp-panel">
        <div class="xzp-panel__body xzp-panel__body--flush">
            <?php if (empty($visits)): ?>
                <p class="xzp-empty"><?php esc_html_e('No visitor data yet. Make sure Visitor Tracking is enabled in Settings.', 'xzeroprotect'); ?></p>
            <?php else: ?>
            <table class="xzp-table xzp-table--full">
                <thead><tr>
                    <th><?php esc_html_e('Time', 'xzeroprotect'); ?></th>
                    <th><?php esc_html_e('IP', 'xzeroprotect'); ?></th>
                    <th><?php esc_html_e('Path', 'xzeroprotect'); ?></th>
                    <th><?php esc_html_e('Browser', 'xzeroprotect'); ?></th>
                    <th><?php esc_html_e('OS', 'xzeroprotect'); ?></th>
                    <th><?php esc_html_e('Device', 'xzeroprotect'); ?></th>
                    <th><?php esc_html_e('Referer', 'xzeroprotect'); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ($visits as $xzp_v): ?>
                    <tr>
                        <td class="xzp-td-time"><?php echo esc_html($xzp_v['visited_at']); ?></td>
                        <td><code><?php echo esc_html($xzp_v['ip']); ?></code></td>
                        <td class="xzp-td-path" title="<?php echo esc_attr($xzp_v['path']); ?>"><?php echo esc_html($xzp_v['path']); ?></td>
                        <td><?php echo esc_html($xzp_v['browser'] . ' ' . $xzp_v['browser_ver']); ?></td>
                        <td><?php echo esc_html($xzp_v['os'] . ' ' . $xzp_v['os_ver']); ?></td>
                        <td>
                            <span class="xzp-badge xzp-badge--<?php echo esc_attr($xzp_v['device_type']); ?>">
                                <?php
                                if ('mobile' === $xzp_v['device_type']) {
                                    echo '📱';
                                } elseif ('tablet' === $xzp_v['device_type']) {
                                    echo '📟';
                                } else {
                                    echo '🖥';
                                }
                                ?>
                                <?php echo esc_html(ucfirst($xzp_v['device_type'])); ?>
                            </span>
                        </td>
                        <td class="xzp-td-referer">
                            <?php if ($xzp_v['referer']): ?>
                                <?php echo esc_html($xzp_v['referer']); ?>
                            <?php else: ?>
                                <span class="xzp-dim">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

</div>
