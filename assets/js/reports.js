/**
 * WooCommerce Product Customizer - Reports JavaScript
 */

jQuery(document).ready(function($) {
    'use strict';

    // Initialize charts when data is available
    if (typeof revenueData !== 'undefined' && typeof zoneData !== 'undefined' && typeof methodData !== 'undefined') {
        initCharts();
    }

    function initCharts() {
        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart');
        if (revenueCtx) {
            new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: revenueData.labels,
                    datasets: [{
                        label: 'Revenue (£)',
                        data: revenueData.revenue,
                        borderColor: 'rgb(102, 126, 234)',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: 'rgb(102, 126, 234)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 6,
                        pointHoverRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 20
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0,0,0,0.1)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return '£' + value.toFixed(2);
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    }
                }
            });
        }

        // Zone Usage Chart
        const zoneCtx = document.getElementById('zoneChart');
        if (zoneCtx) {
            new Chart(zoneCtx, {
                type: 'doughnut',
                data: {
                    labels: zoneData.labels,
                    datasets: [{
                        data: zoneData.data,
                        backgroundColor: [
                            'rgba(102, 126, 234, 0.8)',
                            'rgba(118, 75, 162, 0.8)',
                            'rgba(255, 99, 132, 0.8)',
                            'rgba(54, 162, 235, 0.8)',
                            'rgba(255, 205, 86, 0.8)',
                            'rgba(75, 192, 192, 0.8)',
                            'rgba(153, 102, 255, 0.8)',
                            'rgba(255, 159, 64, 0.8)',
                            'rgba(199, 199, 199, 0.8)',
                            'rgba(83, 102, 255, 0.8)'
                        ],
                        borderColor: [
                            'rgb(102, 126, 234)',
                            'rgb(118, 75, 162)',
                            'rgb(255, 99, 132)',
                            'rgb(54, 162, 235)',
                            'rgb(255, 205, 86)',
                            'rgb(75, 192, 192)',
                            'rgb(153, 102, 255)',
                            'rgb(255, 159, 64)',
                            'rgb(199, 199, 199)',
                            'rgb(83, 102, 255)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                usePointStyle: true,
                                padding: 15,
                                font: {
                                    size: 12
                                }
                            }
                        }
                    },
                    cutout: '50%'
                }
            });
        }

        // Method Usage Chart
        const methodCtx = document.getElementById('methodChart');
        if (methodCtx) {
            new Chart(methodCtx, {
                type: 'bar',
                data: {
                    labels: methodData.labels,
                    datasets: [{
                        label: 'Usage Count',
                        data: methodData.data,
                        backgroundColor: [
                            'rgba(102, 126, 234, 0.8)',
                            'rgba(118, 75, 162, 0.8)'
                        ],
                        borderColor: [
                            'rgb(102, 126, 234)',
                            'rgb(118, 75, 162)'
                        ],
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0,0,0,0.1)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }
    }

    // Date range filter form handling
    $('.reports-filters form').on('submit', function(e) {
        const fromDate = $('#date_from').val();
        const toDate = $('#date_to').val();
        
        if (fromDate && toDate && new Date(fromDate) > new Date(toDate)) {
            e.preventDefault();
            alert('From date cannot be later than To date.');
            return false;
        }
    });

    // Export button click handler
    $('.export-section .button').on('click', function(e) {
        const button = $(this);
        const originalText = button.text();
        
        button.text('Exporting...').prop('disabled', true);
        
        // Re-enable button after 3 seconds (in case of download issues)
        setTimeout(function() {
            button.text(originalText).prop('disabled', false);
        }, 3000);
    });

    // Add loading states to metric cards
    $('.metric-card').each(function() {
        const card = $(this);
        const icon = card.find('.metric-icon');
        
        // Add subtle animation on hover
        card.on('mouseenter', function() {
            icon.css('transform', 'scale(1.1) rotate(5deg)');
        }).on('mouseleave', function() {
            icon.css('transform', 'scale(1) rotate(0deg)');
        });
    });

    // Responsive table handling
    function handleResponsiveTables() {
        $('.table-container table').each(function() {
            const table = $(this);
            const container = table.closest('.table-container');
            
            if (table[0].scrollWidth > container.width()) {
                container.addClass('table-scrollable');
            }
        });
    }

    // Initialize responsive tables
    handleResponsiveTables();
    
    // Re-check on window resize
    $(window).on('resize', function() {
        handleResponsiveTables();
    });
});
