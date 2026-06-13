<?php defined('ABSPATH') || exit; ?>
<div class="xzp-wrap">

    <div class="xzp-header">
        <div class="xzp-header__left">
            <span class="xzp-logo">⚡</span>
            <div>
                <h1>xZeroProtect</h1>
                <p><?php esc_html_e('Firewall &amp; Analytics Dashboard', 'xzeroprotect-wp'); ?></p>
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
                <div class="xzp-card__value"><?php echo number_format((int) $stats['total_visits']); ?></div>
                <div class="xzp-card__label"><?php esc_html_e('Total Visits (30d)', 'xzeroprotect-wp'); ?></div>
                <div class="xzp-card__sub">
                    <?php
                    /* translators: %s: number of visits today */
                    printf( esc_html__('%s today', 'xzeroprotect-wp'), number_format((int) $stats['visits_today']) );
                    ?>
                </div>
            </div>
        </div>
        <div class="xzp-card xzp-card--green">
            <div class="xzp-card__icon">👤</div>
            <div class="xzp-card__body">
                <div class="xzp-card__value"><?php echo number_format((int) $stats['unique_visitors']); ?></div>
                <div class="xzp-card__label"><?php esc_html_e('Unique Visitors (30d)', 'xzeroprotect-wp'); ?></div>
                <div class="xzp-card__sub">
                    <?php
                    /* translators: %s: number of unique visitors today */
                    printf( esc_html__('%s today', 'xzeroprotect-wp'), number_format((int) $stats['unique_today']) );
                    ?>
                </div>
            </div>
        </div>
        <div class="xzp-card xzp-card--red">
            <div class="xzp-card__icon">🚫</div>
            <div class="xzp-card__body">
                <div class="xzp-card__value"><?php echo number_format((int) $stats['total_blocks']); ?></div>
                <div class="xzp-card__label"><?php esc_html_e('Blocked Requests (30d)', 'xzeroprotect-wp'); ?></div>
                <div class="xzp-card__sub">
                    <?php
                    /* translators: %s: number of blocked requests today */
                    printf( esc_html__('%s today', 'xzeroprotect-wp'), number_format((int) $stats['blocked_today']) );
                    ?>
                </div>
            </div>
        </div>
        <?php
        $xzp_block_rate = ($stats['total_visits'] + $stats['total_blocks']) > 0
            ? round($stats['total_blocks'] / ($stats['total_visits'] + $stats['total_blocks']) * 100, 1)
            : 0;
        ?>
        <div class="xzp-card xzp-card--orange">
            <div class="xzp-card__icon">🛡</div>
            <div class="xzp-card__body">
                <div class="xzp-card__value"><?php echo esc_html($xzp_block_rate); ?>%</div>
                <div class="xzp-card__label"><?php esc_html_e('Block Rate (30d)', 'xzeroprotect-wp'); ?></div>
                <div class="xzp-card__sub"><?php esc_html_e('of all traffic', 'xzeroprotect-wp'); ?></div>
            </div>
        </div>
    </div>

    <!-- Chart -->
    <div class="xzp-panel">
        <div class="xzp-panel__header">
            <h2><?php esc_html_e('Traffic Overview', 'xzeroprotect-wp'); ?></h2>
            <div class="xzp-chart-controls">
                <button class="xzp-btn-range active" data-days="7"><?php esc_html_e('7d', 'xzeroprotect-wp'); ?></button>
                <button class="xzp-btn-range" data-days="14"><?php esc_html_e('14d', 'xzeroprotect-wp'); ?></button>
                <button class="xzp-btn-range" data-days="30"><?php esc_html_e('30d', 'xzeroprotect-wp'); ?></button>
            </div>
        </div>
        <div class="xzp-panel__body">
            <canvas id="xzp-chart" height="80"></canvas>
        </div>
    </div>

    <div class="xzp-row">
        <!-- Top Pages -->
        <div class="xzp-panel xzp-panel--half">
            <div class="xzp-panel__header">
                <h2><?php esc_html_e('Top Pages', 'xzeroprotect-wp'); ?></h2>
            </div>
            <div class="xzp-panel__body xzp-panel__body--flush">
                <?php if (empty($pages)): ?>
                    <p class="xzp-empty"><?php esc_html_e('No data yet.', 'xzeroprotect-wp'); ?></p>
                <?php else: ?>
                <table class="xzp-table">
                    <thead><tr>
                        <th><?php esc_html_e('Path', 'xzeroprotect-wp'); ?></th>
                        <th><?php esc_html_e('Hits', 'xzeroprotect-wp'); ?></th>
                        <th><?php esc_html_e('Unique', 'xzeroprotect-wp'); ?></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($pages as $xzp_page): ?>
                        <tr>
                            <td class="xzp-td-path" title="<?php echo esc_attr($xzp_page['path']); ?>"><?php echo esc_html($xzp_page['path']); ?></td>
                            <td><?php echo number_format((int) $xzp_page['hits']); ?></td>
                            <td><?php echo number_format((int) $xzp_page['unique_v']); ?></td>
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
                    <h2><?php esc_html_e('Block Reasons', 'xzeroprotect-wp'); ?></h2>
                </div>
                <div class="xzp-panel__body xzp-panel__body--flush">
                    <?php if (empty($types)): ?>
                        <p class="xzp-empty"><?php esc_html_e('No blocked requests yet.', 'xzeroprotect-wp'); ?></p>
                    <?php else: ?>
                    <?php foreach ($types as $xzp_type): ?>
                        <div class="xzp-bar-row">
                            <span class="xzp-bar-label"><?php echo esc_html($xzp_type['block_type']); ?></span>
                            <span class="xzp-bar-count"><?php echo number_format((int) $xzp_type['total']); ?></span>
                        </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="xzp-panel">
                <div class="xzp-panel__header">
                    <h2><?php esc_html_e('Device Breakdown', 'xzeroprotect-wp'); ?></h2>
                </div>
                <div class="xzp-panel__body">
                    <?php
                    $xzp_dev_map = array_column($devs, 'total', 'device_type');
                    $xzp_total   = array_sum($xzp_dev_map) ?: 1;
                    $xzp_devices = ['desktop' => '🖥', 'mobile' => '📱', 'tablet' => '📟'];
                    foreach ($xzp_devices as $xzp_dtype => $xzp_icon):
                        $xzp_count = (int)($xzp_dev_map[$xzp_dtype] ?? 0);
                        $xzp_pct   = round($xzp_count / $xzp_total * 100);
                    ?>
                    <div class="xzp-device-row">
                        <span class="xzp-device-icon"><?php echo esc_html($xzp_icon); ?></span>
                        <span class="xzp-device-label"><?php echo esc_html(ucfirst($xzp_dtype)); ?></span>
                        <div class="xzp-device-bar">
                            <div class="xzp-device-bar__fill" style="width:<?php echo esc_attr($xzp_pct); ?>%"></div>
                        </div>
                        <span class="xzp-device-pct"><?php echo esc_html($xzp_pct); ?>%</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

</div>
