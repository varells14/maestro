<style>
.sidebar-nav .nav-link {
    font-size: 16px;
}

.sidebar-nav .nav-icon {
    font-size: 18px !important;
}
</style>
<ul class="sidebar-nav" data-coreui="navigation" data-simplebar>
    {{-- Dashboard --}}
    <li class="nav-item">
        <a class="nav-link" href="{{ route('user.dashboard') }}">
            <i class="fa-solid fa-grid-horizontal nav-icon" style="font-size: 14px;"></i> Dashboard

        </a>
    </li>

    {{-- Material Request Dropdown --}}
    <li class="nav-group">
        <a class="nav-link nav-group-toggle" href="#" data-coreui-target="#materialRequestMenu">
            <i class="fa-solid fa-recycle nav-icon" style="font-size: 14px;"></i> Material Request
        </a>
        <ul class="nav-group-items" id="materialRequestMenu">
            <li class="nav-item">
                <a class="nav-link" href="{{ route('user.material_request') }}">Request</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('user.material_approval') }}">Approval</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('user.material_history') }}">History</a>
            </li>
        </ul>
    </li>

    {{-- Purchase Order --}}
    <!-- <li class="nav-item">
        <a class="nav-link" href="{{ route('user.purchase_order') }}">
            <i class="fa-solid fa-shop nav-icon" style="font-size: 14px;"></i> Purchase Order
        </a>
    </li> -->

    {{-- Inventory Dropdown --}}
    <li class="nav-group">
        <a class="nav-link nav-group-toggle" href="#" data-coreui-target="#inventoryMenu">
            <i class="fa-light fa-warehouse nav-icon" style="font-size: 14px;"></i> Inventory
        </a>
        <ul class="nav-group-items" id="inventoryMenu">
            <li class="nav-item">
                <a class="nav-link" href="{{ route('products.index') }}">Stock</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('stock-ins.index') }}">In</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('stock-outs.index') }}">Out</a>
            </li>
        </ul>
    </li>
</ul>
