<?php defined('ABSPATH') || exit; ?>
<div class="xzp-wrap">

    <div class="xzp-header">
        <div class="xzp-header__left">
            <span class="xzp-logo">⚡</span>
            <div>
                <h1>xZeroProtect</h1>
                <p><?php _e('Firewall &amp; Analytics Dashboard', 'xzeroprotect'); ?></p>
            </div>
        </div>
        <div class="xzp-header__right">
            <span class="xzp-mode xzp-mode--<?php echo esc_attr($mode); ?>">
                <?php echo esc_html(strtoupper($mode)); ?>
            </span>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="xzp-cards">
        <div class="xzp-card xzp-card--blue">
            <div class="xzp-card__icon">👁</div>
            <div class="xzp-card__body">
                <div class="xzp-card__value"><?php echo number_format($stats['total_visits']); ?></div>
                <div class="xzp-card__label"><?php _e('Total Visits (30d)', 'xzeroprotect'); ?></div>
                <div class="xzp-card__sub"><?php printf(__('%s today', 'xzeroprotect'), number_format($stats['visits_today'])); ?></div>
            </div>
        </div>
        <div class="xzp-card xzp-card--green">
            <div class="xzp-card__icon">👤</div>
            <div class="xzp-card__body">
                <div class="xzp-card__value"><?php echo number_format($stats['unique_visitors']); ?></div>
                <div class="xzp-card__label"><?php _e('Unique Visitors (30d)', 'xzeroprotect'); ?></div>
                <div class="xzp-card__sub"><?php printf(__('%s today', 'xzeroprotect'), number_format($stats['unique_today'])); ?></div>
            </div>
        </div>
        <div class="xzp-card xzp-card--red">
            <div class="xzp-card__icon">🚫</div>
            <div class="xzp-card__body">
                <div class="xzp-card__value"><?php echo number_format($stats['total_blocks']); ?></div>
                <div class="xzp-card__label"><?php _e('Blocked Requests (30d)', 'xzeroprotect'); ?></div>
                <div class="xzp-card__sub"><?php printf(__('%s today', 'xzeroprotect'), number_format($stats['blocked_today'])); ?></div>
            </div>
        </div>
        <?php
        $block_rate = ($stats['total_visits'] + $stats['total_blocks']) > 0
            ? round($stats['total_blocks'] / ($stats['total_visits'] + $stats['total_blocks']) * 100, 1)
            : 0;
        ?>
        <div class="xzp-card xzp-card--orange">
            <div class="xzp-card__icon">🛡</div>
            <div class="xzp-card__body">
                <div class="xzp-card__value"><?php echo $block_rate; ?>%</div>
                <div class="xzp-card__label"><?php _e('Block Rate (30d)', 'xzeroprotect'); ?></div>
                <div class="xzp-card__sub"><?php _e('of all traffic', 'xzeroprotect'); ?></div>
            </div>
        </div>
    </div>

    <!-- Chart -->
    <div class="xzp-panel">
        <div class="xzp-panel__header">
            <h2><?php _e('Traffic Overview', 'xzeroprotect'); ?></h2>
            <div class="xzp-chart-controls">
                <button class="xzp-btn-range active" data-days="7"><?php _e('7d', 'xzeroprotect'); ?></button>
                <button class="xzp-btn-range" data-days="14"><?php _e('14d', 'xzeroprotect'); ?></button>
                <button class="xzp-btn-range" data-days="30"><?php _e('30d', 'xzeroprotect'); ?></button>
            </div>
        </div>
        <div class="xzp-panel__body">
            <canvas id="xzp-chart" height="80"></canvas>
        </div>
        <script>
        window.xzpChartData = <?php echo json_encode(array_values(array_map(function($day, $data) {
            return ['date' => $day, 'visits' => $data['visits'], 'unique' => $data['unique'], 'blocks' => $data['blocks']];
        }, array_keys($chart), $chart))); ?>;
        </script>
    </div>

    <div class="xzp-row">
        <!-- Top Pages -->
        <div class="xzp-panel xzp-panel--half">
            <div class="xzp-panel__header">
                <h2><?php _e('Top Pages', 'xzeroprotect'); ?></h2>
            </div>
            <div class="xzp-panel__body xzp-panel__body--flush">
                <?php if (empty($pages)): ?>
                    <p class="xzp-empty"><?php _e('No data yet.', 'xzeroprotect'); ?></p>
                <?php else: ?>
                <table class="xzp-table">
                    <thead><tr>
                        <th><?php _e('Path', 'xzeroprotect'); ?></th>
                        <th><?php _e('Hits', 'xzeroprotect'); ?></th>
                        <th><?php _e('Unique', 'xzeroprotect'); ?></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($pages as $p): ?>
                        <tr>
                            <td class="xzp-td-path" title="<?php echo esc_attr($p['path']); ?>"><?php echo esc_html($p['path']); ?></td>
                            <td><?php echo number_format((int)$p['hits']); ?></td>
                            <td><?php echo number_format((int)$p['unique_v']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Block Types + Device Breakdown -->
        <div class="xzp-col">
            <div class="xzp-panel">
                <div class="xzp-panel__header">
                    <h2><?php _e('Block Reasons', 'xzeroprotect'); ?></h2>
                </div>
                <div class="xzp-panel__body xzp-panel__body--flush">
                    <?php if (empty($types)): ?>
                        <p class="xzp-empty"><?php _e('No blocked requests yet.', 'xzeroprotect'); ?></p>
                    <?php else: ?>
                    <?php foreach ($types as $t): ?>
                        <div class="xzp-bar-row">
                            <span class="xzp-bar-label"><?php echo esc_html($t['block_type']); ?></span>
                            <span class="xzp-bar-count"><?php echo number_format((int)$t['total']); ?></span>
                        </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="xzp-panel">
                <div class="xzp-panel__header">
                    <h2><?php _e('Device Breakdown', 'xzeroprotect'); ?></h2>
                </div>
                <div class="xzp-panel__body">
                    <?php
                    $devMap = array_column($devs, 'total', 'device_type');
                    $total  = array_sum($devMap) ?: 1;
                    foreach (['desktop' => '🖥', 'mobile' => '📱', 'tablet' => '📟'] as $type => $icon):
                        $count = (int)($devMap[$type] ?? 0);
                        $pct   = round($count / $total * 100);
                    ?>
                    <div class="xzp-device-row">
                        <span class="xzp-device-icon"><?php echo $icon; ?></span>
                        <span class="xzp-device-label"><?php echo esc_html(ucfirst($type)); ?></span>
                        <div class="xzp-device-bar">
                            <div class="xzp-device-bar__fill" style="width:<?php echo $pct; ?>%"></div>
                        </div>
                        <span class="xzp-device-pct"><?php echo $pct; ?>%</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

</div>
