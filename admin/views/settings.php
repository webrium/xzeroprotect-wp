<?php defined('ABSPATH') || exit; ?>
<div class="xzp-wrap">

    <div class="xzp-header">
        <div class="xzp-header__left">
            <span class="xzp-logo">⚙️</span>
            <div>
                <h1><?php _e('Settings', 'xzeroprotect'); ?></h1>
                <p><?php _e('Configure firewall behavior, detection modules, and data retention', 'xzeroprotect'); ?></p>
            </div>
        </div>
    </div>

    <?php if ($saved): ?>
    <div class="xzp-notice xzp-notice--success">✅ <?php _e('Settings saved successfully.', 'xzeroprotect'); ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['cleared'])): ?>
    <div class="xzp-notice xzp-notice--success">🗑 <?php _e('Data cleared successfully.', 'xzeroprotect'); ?></div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('xzp_save_settings'); ?>
        <input type="hidden" name="action" value="xzp_save_settings">

        <!-- ── Firewall Mode ──────────────────────────────────────────────── -->
        <div class="xzp-panel">
            <div class="xzp-panel__header"><h2><?php _e('Firewall Mode', 'xzeroprotect'); ?></h2></div>
            <div class="xzp-panel__body">
                <div class="xzp-mode-cards">
                    <?php foreach (['production' => ['🛡', __('Production', 'xzeroprotect'), __('Block and log all detected threats.', 'xzeroprotect')],
                                    'learning'   => ['📖', __('Learning', 'xzeroprotect'),   __('Log only — never block. Use to tune rules before going live.', 'xzeroprotect')],
                                    'off'        => ['⏸', __('Off', 'xzeroprotect'),         __('Firewall is completely disabled.', 'xzeroprotect')],
                                   ] as $val => [$icon, $label, $desc]): ?>
                    <label class="xzp-mode-card <?php echo $settings['mode'] === $val ? 'active' : ''; ?>">
                        <input type="radio" name="mode" value="<?php echo $val; ?>" <?php checked($settings['mode'], $val); ?> hidden>
                        <span class="xzp-mode-card__icon"><?php echo $icon; ?></span>
                        <strong><?php echo $label; ?></strong>
                        <span><?php echo $desc; ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- ── Visitor Tracking ──────────────────────────────────────────── -->
        <div class="xzp-panel">
            <div class="xzp-panel__header"><h2><?php _e('Visitor Tracking', 'xzeroprotect'); ?></h2></div>
            <div class="xzp-panel__body">
                <label class="xzp-toggle">
                    <input type="checkbox" name="tracking_enabled" value="1" <?php checked($settings['tracking_enabled']); ?>>
                    <span class="xzp-toggle__slider"></span>
                    <?php _e('Enable real visitor tracking (stores verified visits in database)', 'xzeroprotect'); ?>
                </label>
            </div>
        </div>

        <!-- ── Detection Checks ──────────────────────────────────────────── -->
        <div class="xzp-panel">
            <div class="xzp-panel__header"><h2><?php _e('Detection Modules', 'xzeroprotect'); ?></h2></div>
            <div class="xzp-panel__body">
                <div class="xzp-checks-grid">
                    <?php $checks = [
                        'check_crawler'       => [__('Crawler Verification', 'xzeroprotect'),  __('Exempt verified crawlers (Googlebot, Bingbot…) from all checks via double-DNS.', 'xzeroprotect')],
                        'check_rate_limit'    => [__('Rate Limiting', 'xzeroprotect'),          __('Block IPs that exceed request threshold.', 'xzeroprotect')],
                        'check_blocked_path'  => [__('Blocked Paths', 'xzeroprotect'),          __('Block requests to suspicious paths (.env, phpmyadmin, web shells…).', 'xzeroprotect')],
                        'check_user_agent'    => [__('User-Agent Detection', 'xzeroprotect'),   __('Block known scanner and exploit tool signatures.', 'xzeroprotect')],
                        'check_payload'       => [__('Payload Scanning', 'xzeroprotect'),       __('Scan GET/POST/cookies for SQLi, XSS, path traversal, and command injection.', 'xzeroprotect')],
                        'check_custom_rules'  => [__('Custom Rules', 'xzeroprotect'),           __('Run any rules registered via the xZeroProtect PHP API.', 'xzeroprotect')],
                    ];
                    foreach ($checks as $key => [$label, $desc]): ?>
                    <div class="xzp-check-item">
                        <label class="xzp-toggle">
                            <input type="checkbox" name="<?php echo $key; ?>" value="1" <?php checked($settings[$key]); ?>>
                            <span class="xzp-toggle__slider"></span>
                            <strong><?php echo $label; ?></strong>
                        </label>
                        <p class="xzp-check-desc"><?php echo $desc; ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- ── Extra Blocking Options ────────────────────────────────────── -->
        <div class="xzp-panel">
            <div class="xzp-panel__header"><h2><?php _e('Extra Blocking Options', 'xzeroprotect'); ?></h2></div>
            <div class="xzp-panel__body">
                <p class="xzp-hint"><?php _e('These are disabled by default to avoid blocking legitimate API clients.', 'xzeroprotect'); ?></p>
                <div class="xzp-checks-grid">
                    <?php $extras = [
                        'block_php_extension' => __('Block .php extension in URLs (for fully-routed apps)', 'xzeroprotect'),
                        'block_curl'          => __('Block curl/ user-agent', 'xzeroprotect'),
                        'block_wget'          => __('Block wget/ user-agent', 'xzeroprotect'),
                        'block_python'        => __('Block python-requests user-agent', 'xzeroprotect'),
                        'block_go_http'       => __('Block go-http-client user-agent', 'xzeroprotect'),
                    ];
                    foreach ($extras as $key => $label): ?>
                    <div class="xzp-check-item">
                        <label class="xzp-toggle">
                            <input type="checkbox" name="<?php echo $key; ?>" value="1" <?php checked($settings[$key]); ?>>
                            <span class="xzp-toggle__slider"></span>
                            <?php echo $label; ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- ── Rate Limiting ─────────────────────────────────────────────── -->
        <div class="xzp-panel">
            <div class="xzp-panel__header"><h2><?php _e('Rate Limiting', 'xzeroprotect'); ?></h2></div>
            <div class="xzp-panel__body xzp-panel__body--fields">
                <div class="xzp-field">
                    <label><?php _e('Max Requests', 'xzeroprotect'); ?></label>
                    <input type="number" name="rate_limit_max" min="1" value="<?php echo esc_attr($settings['rate_limit_max']); ?>">
                    <span class="xzp-field-hint"><?php _e('requests allowed per window', 'xzeroprotect'); ?></span>
                </div>
                <div class="xzp-field">
                    <label><?php _e('Window (seconds)', 'xzeroprotect'); ?></label>
                    <input type="number" name="rate_limit_window" min="1" value="<?php echo esc_attr($settings['rate_limit_window']); ?>">
                    <span class="xzp-field-hint"><?php _e('rolling window size', 'xzeroprotect'); ?></span>
                </div>
            </div>
        </div>

        <!-- ── Auto-Ban ──────────────────────────────────────────────────── -->
        <div class="xzp-panel">
            <div class="xzp-panel__header"><h2><?php _e('Auto-Ban', 'xzeroprotect'); ?></h2></div>
            <div class="xzp-panel__body xzp-panel__body--fields">
                <div class="xzp-field xzp-field--full">
                    <label class="xzp-toggle">
                        <input type="checkbox" name="auto_ban_enabled" value="1" <?php checked($settings['auto_ban_enabled']); ?>>
                        <span class="xzp-toggle__slider"></span>
                        <?php _e('Enable automatic IP banning', 'xzeroprotect'); ?>
                    </label>
                </div>
                <div class="xzp-field">
                    <label><?php _e('Violations Before Ban', 'xzeroprotect'); ?></label>
                    <input type="number" name="auto_ban_threshold" min="1" value="<?php echo esc_attr($settings['auto_ban_threshold']); ?>">
                </div>
                <div class="xzp-field">
                    <label><?php _e('Ban Duration (seconds)', 'xzeroprotect'); ?></label>
                    <input type="number" name="auto_ban_duration" min="60" value="<?php echo esc_attr($settings['auto_ban_duration']); ?>">
                    <span class="xzp-field-hint"><?php _e('86400 = 24 hours', 'xzeroprotect'); ?></span>
                </div>
                <div class="xzp-field">
                    <label><?php _e('Permanent After N Bans', 'xzeroprotect'); ?></label>
                    <input type="number" name="auto_ban_permanent" min="1" value="<?php echo esc_attr($settings['auto_ban_permanent']); ?>">
                </div>
            </div>
        </div>

        <!-- ── WordPress Safety ──────────────────────────────────────────── -->
        <div class="xzp-panel">
            <div class="xzp-panel__header"><h2><?php _e('WordPress Safety', 'xzeroprotect'); ?></h2></div>
            <div class="xzp-panel__body">
                <label class="xzp-toggle">
                    <input type="checkbox" name="wp_safe_paths" value="1" <?php checked($settings['wp_safe_paths']); ?>>
                    <span class="xzp-toggle__slider"></span>
                    <?php _e('Always whitelist WordPress core paths (wp-admin, wp-login, wp-cron, wp-json)', 'xzeroprotect'); ?>
                </label>
                <p class="xzp-hint"><?php _e('Strongly recommended. Disabling this may lock you out of wp-admin.', 'xzeroprotect'); ?></p>
            </div>
        </div>

        <!-- ── Whitelist ─────────────────────────────────────────────────── -->
        <div class="xzp-panel">
            <div class="xzp-panel__header"><h2><?php _e('Whitelist', 'xzeroprotect'); ?></h2></div>
            <div class="xzp-panel__body xzp-panel__body--fields">
                <div class="xzp-field xzp-field--full">
                    <label><?php _e('Whitelisted IPs', 'xzeroprotect'); ?></label>
                    <textarea name="whitelist_ips" rows="4" placeholder="1.2.3.4&#10;10.0.0.0/8"><?php echo esc_textarea($settings['whitelist_ips']); ?></textarea>
                    <span class="xzp-field-hint"><?php _e('One IP or CIDR range per line. These IPs bypass all firewall checks.', 'xzeroprotect'); ?></span>
                </div>
                <div class="xzp-field xzp-field--full">
                    <label><?php _e('Whitelisted Paths', 'xzeroprotect'); ?></label>
                    <textarea name="whitelist_paths" rows="4" placeholder="/health&#10;/api/webhook"><?php echo esc_textarea($settings['whitelist_paths']); ?></textarea>
                    <span class="xzp-field-hint"><?php _e('One path prefix per line.', 'xzeroprotect'); ?></span>
                </div>
            </div>
        </div>

        <!-- ── Block Response ────────────────────────────────────────────── -->
        <div class="xzp-panel">
            <div class="xzp-panel__header"><h2><?php _e('Block Response', 'xzeroprotect'); ?></h2></div>
            <div class="xzp-panel__body xzp-panel__body--fields">
                <div class="xzp-field">
                    <label><?php _e('HTTP Status Code', 'xzeroprotect'); ?></label>
                    <select name="block_code">
                        <?php foreach ([403 => '403 Forbidden', 429 => '429 Too Many Requests', 503 => '503 Service Unavailable'] as $code => $label): ?>
                        <option value="<?php echo $code; ?>" <?php selected($settings['block_code'], $code); ?>><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="xzp-field">
                    <label><?php _e('Message', 'xzeroprotect'); ?></label>
                    <input type="text" name="block_message" value="<?php echo esc_attr($settings['block_message']); ?>">
                </div>
            </div>
        </div>

        <!-- ── Data Retention ────────────────────────────────────────────── -->
        <div class="xzp-panel">
            <div class="xzp-panel__header"><h2><?php _e('Data Retention', 'xzeroprotect'); ?></h2></div>
            <div class="xzp-panel__body xzp-panel__body--fields">
                <div class="xzp-field">
                    <label><?php _e('Keep Data (days)', 'xzeroprotect'); ?></label>
                    <input type="number" name="keep_days" min="1" max="365" value="<?php echo esc_attr($settings['keep_days']); ?>">
                    <span class="xzp-field-hint"><?php _e('Visitor and block logs older than this are deleted daily.', 'xzeroprotect'); ?></span>
                </div>
            </div>
        </div>

        <div class="xzp-actions">
            <button type="submit" class="xzp-btn xzp-btn--primary">
                💾 <?php _e('Save Settings', 'xzeroprotect'); ?>
            </button>
        </div>
    </form>

    <!-- ── Danger Zone ───────────────────────────────────────────────────── -->
    <div class="xzp-panel xzp-panel--danger">
        <div class="xzp-panel__header"><h2>⚠️ <?php _e('Danger Zone', 'xzeroprotect'); ?></h2></div>
        <div class="xzp-panel__body xzp-danger-zone">
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
                  onsubmit="return confirm('<?php _e('Are you sure? This cannot be undone.', 'xzeroprotect'); ?>')">
                <?php wp_nonce_field('xzp_clear_data'); ?>
                <input type="hidden" name="action" value="xzp_clear_data">
                <div class="xzp-danger-row">
                    <select name="clear_type">
                        <option value="visits"><?php _e('Clear visitor logs only', 'xzeroprotect'); ?></option>
                        <option value="blocks"><?php _e('Clear blocked requests only', 'xzeroprotect'); ?></option>
                        <option value="all"><?php _e('Clear all data', 'xzeroprotect'); ?></option>
                    </select>
                    <button type="submit" class="xzp-btn xzp-btn--danger">
                        🗑 <?php _e('Clear Data', 'xzeroprotect'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
