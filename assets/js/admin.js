/* xZeroProtect Admin JS */
(function ($) {
    'use strict';

    // ── Chart ─────────────────────────────────────────────────────────────────

    var chartCanvas = document.getElementById('xzp-chart');
    var chartInstance = null;

    function renderChart(data) {
        if (!chartCanvas || !data || !data.length) return;

        var labels  = data.map(function (d) { return d.date.slice(5); }); // MM-DD
        var visits  = data.map(function (d) { return d.visits; });
        var unique  = data.map(function (d) { return d.unique; });
        var blocked = data.map(function (d) { return d.blocks; });

        if (chartInstance) { chartInstance.destroy(); }

        chartInstance = new Chart(chartCanvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: xzpData.i18n.visits,
                        data: visits,
                        backgroundColor: 'rgba(59,130,246,0.15)',
                        borderColor: 'rgba(59,130,246,1)',
                        borderWidth: 2,
                        borderRadius: 4,
                        type: 'bar',
                    },
                    {
                        label: xzpData.i18n.unique,
                        data: unique,
                        borderColor: 'rgba(16,185,129,1)',
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        pointRadius: 3,
                        tension: 0.3,
                        type: 'line',
                        yAxisID: 'y',
                    },
                    {
                        label: xzpData.i18n.blocked,
                        data: blocked,
                        backgroundColor: 'rgba(239,68,68,0.15)',
                        borderColor: 'rgba(239,68,68,1)',
                        borderWidth: 2,
                        borderRadius: 4,
                        type: 'bar',
                    },
                ]
            },
            options: {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top', labels: { boxWidth: 12, font: { size: 12 } } },
                    tooltip: { bodyFont: { size: 12 }, titleFont: { size: 12 } },
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 11 } } },
                    y: { grid: { color: '#f3f4f6' }, ticks: { font: { size: 11 } }, beginAtZero: true },
                }
            }
        });
    }

    // Initial render from inline data
    if (typeof window.xzpChartData !== 'undefined') {
        // Defer until Chart.js is ready
        window.addEventListener('load', function () {
            if (typeof Chart !== 'undefined') {
                renderChart(window.xzpChartData);
            }
        });
    }

    // Range buttons
    $(document).on('click', '.xzp-btn-range', function () {
        $('.xzp-btn-range').removeClass('active');
        $(this).addClass('active');

        var days = $(this).data('days');
        $.post(xzpData.ajaxUrl, {
            action: 'xzp_get_chart',
            nonce:  xzpData.nonce,
            days:   days,
        }, function (response) {
            if (response.success) {
                renderChart(Object.entries(response.data).map(function (e) {
                    return { date: e[0], visits: e[1].visits, unique: e[1].unique, blocks: e[1].blocks };
                }));
            }
        });
    });

    // ── Mode card selection ───────────────────────────────────────────────────

    $(document).on('change', '.xzp-mode-card input[type=radio]', function () {
        $('.xzp-mode-card').removeClass('active');
        $(this).closest('.xzp-mode-card').addClass('active');
    });

    // ── Load Chart.js from CDN if not already present ─────────────────────────

    if (chartCanvas && typeof Chart === 'undefined') {
        var script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js';
        script.onload = function () {
            if (typeof window.xzpChartData !== 'undefined') {
                renderChart(window.xzpChartData);
            }
        };
        document.head.appendChild(script);
    }

}(jQuery));
