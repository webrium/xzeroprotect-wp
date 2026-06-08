<?php defined('ABSPATH') || exit; ?>
<div class="xzp-wrap">

    <div class="xzp-header">
        <div class="xzp-header__left">
            <span class="xzp-logo">⚙️</span>
            <div>
                <h1><?php esc_html_e('Settings', 'xzeroprotect-wp'); ?></h1>
                <p><?php esc_html_e('Configure firewall behavior, detection modules, and data retention', 'xzeroprotect-wp'); ?></p>
            </div>
        </div>
    </div>

    <?php if ($saved): ?>
    <div class="xzp-notice xzp-notice--success">✅ <?php esc_html_e('Settings saved successfully.', 'xzeroprotect-wp'); ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['cleared'])): ?>
    <div class="xzp-notice xzp-notice--success">🗑 <?php esc_html_e('Data cleared successfully.', 'xzeroprotect-wp'); ?></div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('xzp_save_settings'); ?>
        <input type="hidden" name="action" value="xzp_save_settings">

        <!-- ── Firewall Mode ──────────────────────────────────────────────── -->
        <div class="xzp-panel">
            <div class="xzp-panel__header"><h2><?php esc_html_e('Firewall Mode', 'xzeroprotect-wp'); ?></h2></div>
            <div class="xzp-panel__body">
                <div class="xzp-mode-cards">
                    <?php foreach (['production' => ['🛡', __('Production', 'xzeroprotect-wp'), __('Block and log all detected threats.', 'xzeroprotect-wp')],
                                    'learning'   => ['📖', __('Learning', 'xzeroprotect-wp'),   __('Log only — never block. Use to tune rules before going live.', 'xzeroprotect-wp')],
                                    'off'        => ['⏸', __('Off', 'xzeroprotect-wp'),         __('Firewall is completely disabled.', 'xzeroprotect-wp')],
                                   ] as $val => [$icon, $label, $desc]): ?>
                    <label class="xzp-mode-card <?php echo $settings['mode'] === $val ? 'active' : ''; ?>">
                        <input type="radio" name="mode" value="<?php echo esc_attr($val); ?>" <?php checked($settings['mode'], $val); ?> hidden>
                        <span class="xzp-mode-card__icon"><?php echo esc_html($icon); ?></span>
                        <strong><?php echo esc_html($label); ?></strong>
                        <span><?php echo esc_html($desc); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- ── Visitor Tracking ──────────────────────────────────────────── -->
        <div class="xzp-panel">
            <div class="xzp-panel__header"><h2><?php esc_html_e('Visitor Tracking', 'xzeroprotect-wp'); ?></h2></div>
            <div class="xzp-panel__body">
                <label class="xzp-toggle">
                    <input type="checkbox" name="tracking_enabled" value="1" <?php checked($settings['tracking_enabled']); ?>>
                    <span class="xzp-toggle__slider"></span>
                    <?php esc_html_e('Enable real visitor tracking (stores verified visits in database)', 'xzeroprotect-wp'); ?>
                </label>
            </div>
        </div>

        <!-- ── Detection Checks ──────────────────────────────────────────── -->
        <div class="xzp-panel">
            <div class="xzp-panel__header"><h2><?php esc_html_e('Detection Modules', 'xzeroprotect-wp'); ?></h2></div>
            <div class="xzp-panel__body">
                <div class="xzp-checks-grid">
                    <?php $checks = [
                        'check_crawler'       => [__('Crawler Verification', 'xzeroprotect-wp'),  __('Exempt verified crawlers (Googlebot, Bingbot…) from all checks via double-DNS.', 'xzeroprotect-wp')],
                        'check_rate_limit'    => [__('Rate Limiting', 'xzeroprotect-wp'),          __('Block IPs that exceed request threshold.', 'xzeroprotect-wp')],
                        'check_blocked_path'  => [__('Blocked Paths', 'xzeroprotect-wp'),          __('Block requests to suspicious paths (.env, phpmyadmin, web shells…).', 'xzeroprotect-wp')],
                        'check_user_agent'    => [__('User-Agent Detection', 'xzeroprotect-wp'),   __('Block known scanner and exploit tool signatures.', 'xzeroprotect-wp')],
                        'check_payload'       => [__('Payload Scanning', 'xzeroprotect-wp'),       __('Scan GET/POST/cookies for SQLi, XSS, path traversal, and command injection.', 'xzeroprotect-wp')],
                        'check_custom_rules'  => [__('Custom Rules', 'xzeroprotect-wp'),           __('Run any rules registered via the xZeroProtect PHP API.', 'xzeroprotect-wp')],
                    ];
                    foreach ($checks as $key => [$label, $desc]): ?>
                    <div class="xzp-check-item">
                        <label class="xzp-toggle">
                            <input type="checkbox" name="<?php echo esc_attr($key); ?>" value="1" <?php checked($settings[$key]); ?>>
                            <span class="xzp-toggle__slider"></span>
                            <strong><?php echo esc_html($label); ?></strong>
                        </label>
                        <p class="xzp-check-desc"><?php echo esc_html($desc); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- ── Extra Blocking Options ────────────────────────────────────── -->
        <div class="xzp-panel">
            <div class="xzp-panel__header"><h2><?php esc_html_e('Extra Blocking Options', 'xzeroprotect-wp'); ?></h2></div>
            <div class="xzp-panel__body">
                <p class="xzp-hint"><?php esc_html_e('These are disabled by default to avoid blocking legitimate API clients.', 'xzeroprotect-wp'); ?></p>
                <div class="xzp-checks-grid">
                    <?php $extras = [
                        'block_php_extension' => __('Block .php extension in URLs (for fully-routed apps)', 'xzeroprotect-wp'),
                        'block_curl'          => __('Block curl/ user-agent', 'xzeroprotect-wp'),
                        'block_wget'          => __('Block wget/ user-agent', 'xzeroprotect-wp'),
                        'block_python'        => __('Block python-requests user-agent', 'xzeroprotect-wp'),
                        'block_go_http'       => __('Block go-http-client user-agent', 'xzeroprotect-wp'),
                    ];
                    foreach ($extras as $key => $label): ?>
                    <div class="xzp-check-item">
                        <label class="xzp-toggle">
                            <input type="checkbox" name="<?php echo esc_attr($key); ?>" value="1" <?php checked($settings[$key]); ?>>
                            <span class="xzp-toggle__slider"></span>
                            <?php echo esc_html($label); ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- ── Rate Limiting ─────────────────────────────────────────────── -->
        <div class="xzp-panel">
            <div class="xzp-panel__header"><h2><?php esc_html_e('Rate Limiting', 'xzeroprotect-wp'); ?></h2></div>
            <div class="xzp-panel__body xzp-panel__body--fields">
                <div class="xzp-field">
                    <label><?php esc_html_e('Max Requests', 'xzeroprotect-wp'); ?></label>
                    <input type="number" name="rate_limit_max" min="1" value="<?php echo esc_attr($settings['rate_limit_max']); ?>">
                    <span class="xzp-field-hint"><?php esc_html_e('requests allowed per window', 'xzeroprotect-wp'); ?></span>
                </div>
                <div class="xzp-field">
                    <label><?php esc_html_e('Window (seconds)', 'xzeroprotect-wp'); ?></label>
                    <input type="number" name="rate_limit_window" min="1" value="<?php echo esc_attr($settings['rate_limit_window']); ?>">
                    <span class="xzp-field-hint"><?php esc_html_e('rolling window size', 'xzeroprotect-wp'); ?></span>
                </div>
            </div>
        </div>

        <!-- ── Auto-Ban ──────────────────────────────────────────────────── -->
        <div class="xzp-panel">
            <div class="xzp-panel__header"><h2><?php esc_html_e('Auto-Ban', 'xzeroprotect-wp'); ?></h2></div>
            <div class="xzp-panel__body xzp-panel__body--fields">
                <div class="xzp-field xzp-field--full">
                    <label class="xzp-toggle">
                        <input type="checkbox" name="auto_ban_enabled" value="1" <?php checked($settings['auto_ban_enabled']); ?>>
                        <span class="xzp-toggle__slider"></span>
                        <?php esc_html_e('Enable automatic IP banning', 'xzeroprotect-wp'); ?>
                    </label>
                </div>
                <div class="xzp-field">
                    <label><?php esc_html_e('Violations Before Ban', 'xzeroprotect-wp'); ?></label>
                    <input type="number" name="auto_ban_threshold" min="1" value="<?php echo esc_attr($settings['auto_ban_threshold']); ?>">
                </div>
                <div class="xzp-field">
                    <label><?php esc_html_e('Ban Duration (seconds)', 'xzeroprotect-wp'); ?></label>
                    <input type="number" name="auto_ban_duration" min="60" value="<?php echo esc_attr($settings['auto_ban_duration']); ?>">
                    <span class="xzp-field-hint"><?php esc_html_e('86400 = 24 hours', 'xzeroprotect-wp'); ?></span>
                </div>
                <div class="xzp-field">
                    <label><?php esc_html_e('Permanent After N Bans', 'xzeroprotect-wp'); ?></label>
                    <input type="number" name="auto_ban_permanent" min="1" value="<?php echo esc_attr($settings['auto_ban_permanent']); ?>">
                </div>
            </div>
        </div>

        <!-- ── WordPress Safety ──────────────────────────────────────────── -->
        <div class="xzp-panel">
            <div class="xzp-panel__header"><h2><?php esc_html_e('WordPress Safety', 'xzeroprotect-wp'); ?></h2></div>
            <div class="xzp-panel__body">
                <label class="xzp-toggle">
                    <input type="checkbox" name="wp_safe_paths" value="1" <?php checked($settings['wp_safe_paths']); ?>>
                    <span class="xzp-toggle__slider"></span>
                    <?php esc_html_e('Always whitelist WordPress core paths (wp-admin, wp-login, wp-cron, wp-json)', 'xzeroprotect-wp'); ?>
                </label>
                <p class="xzp-hint"><?php esc_html_e('Strongly recommended. Disabling this may lock you out of wp-admin.', 'xzeroprotect-wp'); ?></p>
            </div>
        </div>

        <!-- ── Whitelist ─────────────────────────────────────────────────── -->
        <div class="xzp-panel">
            <div class="xzp-panel__header"><h2><?php esc_html_e('Whitelist', 'xzeroprotect-wp'); ?></h2></div>
            <div class="xzp-panel__body xzp-panel__body--fields">
                <div class="xzp-field xzp-field--full">
                    <label><?php esc_html_e('Whitelisted IPs', 'xzeroprotect-wp'); ?></label>
                    <textarea name="whitelist_ips" rows="4" placeholder="1.2.3.4&#10;10.0.0.0/8"><?php echo esc_textarea($settings['whitelist_ips']); ?></textarea>
                    <span class="xzp-field-hint"><?php esc_html_e('One IP or CIDR range per line. These IPs bypass all firewall checks.', 'xzeroprotect-wp'); ?></span>
                </div>
                <div class="xzp-field xzp-field--full">
                    <label><?php esc_html_e('Whitelisted Paths', 'xzeroprotect-wp'); ?></label>
                    <textarea name="whitelist_paths" rows="4" placeholder="/health&#10;/api/webhook"><?php echo esc_textarea($settings['whitelist_paths']); ?></textarea>
                    <span class="xzp-field-hint"><?php esc_html_e('One path prefix per line.', 'xzeroprotect-wp'); ?></span>
                </div>
            </div>
        </div>

        <!-- ── Block Response ────────────────────────────────────────────── -->
        <div class="xzp-panel">
            <div class="xzp-panel__header"><h2><?php esc_html_e('Block Response', 'xzeroprotect-wp'); ?></h2></div>
            <div class="xzp-panel__body xzp-panel__body--fields">
                <div class="xzp-field">
                    <label><?php esc_html_e('HTTP Status Code', 'xzeroprotect-wp'); ?></label>
                    <select name="block_code">
                        <?php foreach ([403 => '403 Forbidden', 429 => '429 Too Many Requests', 503 => '503 Service Unavailable'] as $code => $label): ?>
                        <option value="<?php echo esc_attr($code); ?>" <?php selected($settings['block_code'], $code); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="xzp-field">
                    <label><?php esc_html_e('Message', 'xzeroprotect-wp'); ?></label>
                    <input type="text" name="block_message" value="<?php echo esc_attr($settings['block_message']); ?>">
                </div>
            </div>
        </div>

        <!-- ── Data Retention ────────────────────────────────────────────── -->
        <div class="xzp-panel">
            <div class="xzp-panel__header"><h2><?php esc_html_e('Data Retention', 'xzeroprotect-wp'); ?></h2></div>
            <div class="xzp-panel__body xzp-panel__body--fields">
                <div class="xzp-field">
                    <label><?php esc_html_e('Keep Data (days)', 'xzeroprotect-wp'); ?></label>
                    <input type="number" name="keep_days" min="1" max="365" value="<?php echo esc_attr($settings['keep_days']); ?>">
                    <span class="xzp-field-hint"><?php esc_html_e('Visitor and block logs older than this are deleted daily.', 'xzeroprotect-wp'); ?></span>
                </div>
            </div>
        </div>

        <div class="xzp-actions">
            <button type="submit" class="xzp-btn xzp-btn--primary">
                💾 <?php esc_html_e('Save Settings', 'xzeroprotect-wp'); ?>
            </button>
        </div>
    </form>

    <!-- ── Danger Zone ───────────────────────────────────────────────────── -->
    <div class="xzp-panel xzp-panel--danger">
        <div class="xzp-panel__header"><h2>⚠️ <?php esc_html_e('Danger Zone', 'xzeroprotect-wp'); ?></h2></div>
        <div class="xzp-panel__body xzp-danger-zone">
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
                  onsubmit="return confirm('<?php echo esc_js(__('Are you sure? This cannot be undone.', 'xzeroprotect-wp')); ?>')">
                <?php wp_nonce_field('xzp_clear_data'); ?>
                <input type="hidden" name="action" value="xzp_clear_data">
                <div class="xzp-danger-row">
                    <select name="clear_type">
                        <option value="visits"><?php esc_html_e('Clear visitor logs only', 'xzeroprotect-wp'); ?></option>
                        <option value="blocks"><?php esc_html_e('Clear blocked requests only', 'xzeroprotect-wp'); ?></option>
                        <option value="all"><?php esc_html_e('Clear all data', 'xzeroprotect-wp'); ?></option>
                    </select>
                    <button type="submit" class="xzp-btn xzp-btn--danger">
                        🗑 <?php esc_html_e('Clear Data', 'xzeroprotect-wp'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
