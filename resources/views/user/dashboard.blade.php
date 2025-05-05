@extends('layouts.app')

@section('extra_css')
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
<style>
    .card-stats {
        padding: 1rem;
        display: flex;
        align-items: center;
    }
    
    .card-stats-icon {
        border-radius: 12px;
        width: 70px;
        height: 70px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
    }
    
    .card-stats-icon i {
        font-size: 2rem;
        color: white;
    }
    
    .card-stats-info {
        flex: 1;
    }
    
    .card-stats-title {
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        margin-bottom: 0.5rem;
    }
    
    .card-stats-value {
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: 0;
    }
    
    .bg-primary {
        background-color: #4e73df !important;
    }
    
    .bg-success {
        background-color: #1cc88a !important;
    }
    
    .bg-warning {
        background-color: #f6c23e !important;
    }
    
    .bg-info {
        background-color: #36b9cc !important;
    }
    
    .chart-container {
        position: relative;
        height: 370px;
        margin: 0 auto;
    }
    
    .card {
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        transition: transform 0.2s ease;
    }
    
    .card:hover {
        transform: translateY(-5px);
    }
</style>
@endsection

@section('content')
<div class="container-fluid py-4">
    <!-- Overview Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100">
                <div class="card-body card-stats">
                    <div class="card-stats-icon bg-primary">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="card-stats-info">
                        <div class="card-stats-title text-primary">Total Materials</div>
                        <div class="card-stats-value">{{ $totalProducts }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100">
                <div class="card-body card-stats">
                    <div class="card-stats-icon bg-success">
                        <i class="fas fa-arrow-circle-down"></i>
                    </div>
                    <div class="card-stats-info">
                        <div class="card-stats-title text-success">Total Stock Ins</div>
                        <div class="card-stats-value">{{ $totalStockins }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100">
                <div class="card-body card-stats">
                    <div class="card-stats-icon bg-warning">
                        <i class="fas fa-arrow-circle-up"></i>
                    </div>
                    <div class="card-stats-info">
                        <div class="card-stats-title text-warning">Total Stock Outs</div>
                        <div class="card-stats-value">{{ $totalStockouts }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100">
                <div class="card-body card-stats">
                    <div class="card-stats-icon bg-info">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="card-stats-info">
                        <div class="card-stats-title text-info">Material Requests</div>
                        <div class="card-stats-value">{{ $totalMaterialRequests }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Low Stock Materials Chart -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Low Stock Materials</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="lowStockChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('extra_js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Low Stock Materials Chart
        var lowStockCtx = document.getElementById('lowStockChart').getContext('2d');
        
        // Get data from your Laravel variables
        var lowStockLabels = [@foreach($lowStockProducts as $product) '{{ $product->name }}', @endforeach];
        var lowStockData = [@foreach($lowStockProducts as $product) {{ $product->stock }}, @endforeach];
        var lowStockCategories = [@foreach($lowStockProducts as $product) '{{ $product->category->name ?? "Uncategorized" }}', @endforeach];
        
        // Generate background colors based on categories
        var backgroundColors = lowStockCategories.map(function(category) {
            switch(category) {
                case 'Building Materials': return 'rgba(78, 115, 223, 0.8)';
                case 'Tools & Equipment': return 'rgba(28, 200, 138, 0.8)';
                case 'Electrical': return 'rgba(54, 185, 204, 0.8)';
                case 'Plumbing & Sanitary': return 'rgba(246, 194, 62, 0.8)';
                default: return 'rgba(231, 74, 59, 0.8)';
            }
        });
        
        var lowStockChart = new Chart(lowStockCtx, {
            type: 'horizontalBar',
            data: {
                labels: lowStockLabels,
                datasets: [{
                    label: 'Current Stock',
                    data: lowStockData,
                    backgroundColor: backgroundColors,
                    borderColor: backgroundColors.map(color => color.replace('0.8', '1')),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    xAxes: [{
                        ticks: {
                            beginAtZero: true,
                            fontColor: '#5a5c69'
                        },
                        gridLines: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            zeroLineColor: 'rgba(0, 0, 0, 0.1)'
                        }
                    }],
                    yAxes: [{
                        ticks: {
                            fontColor: '#5a5c69'
                        },
                        gridLines: {
                            display: false
                        }
                    }]
                },
                legend: {
                    display: false
                },
                tooltips: {
                    backgroundColor: "rgb(255,255,255)",
                    bodyFontColor: "#858796",
                    titleMarginBottom: 10,
                    titleFontColor: '#6e707e',
                    titleFontSize: 14,
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    xPadding: 15,
                    yPadding: 15,
                    displayColors: false,
                    caretPadding: 10,
                    callbacks: {
                        label: function(tooltipItem, data) {
                            return 'Stock: ' + tooltipItem.xLabel;
                        },
                        afterLabel: function(tooltipItem, data) {
                            return 'Category: ' + lowStockCategories[tooltipItem.index];
                        }
                    }
                }
            }
        });
    });
</script>
@endsection