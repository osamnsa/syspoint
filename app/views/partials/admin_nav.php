<?php
declare(strict_types=1);

/**
 * Sidebar menu: groups of links, each guarded by an area from ADMIN_AREAS
 * ('*' = everyone, 'admin' = admins only). A group with no visible links is
 * hidden, so each staff member only sees the parts of the admin they use.
 * Later phases add their pages here (and their paths to ADMIN_AREA_PATHS).
 */

$adminNavGroups = [
    ['label' => null, 'items' => [
        ['path' => 'admin', 'label' => 'Overview', 'icon' => 'grid', 'area' => '*'],
    ]],
    ['label' => 'Sales & CRM', 'items' => [
        ['path' => 'admin/crm', 'label' => 'CRM', 'icon' => 'pulse', 'area' => 'sales'],
        ['path' => 'admin/customers', 'label' => 'Customers', 'icon' => 'users', 'area' => 'sales'],
        ['path' => 'admin/deals', 'label' => 'Deals Pipeline', 'icon' => 'funnel', 'area' => 'sales'],
        ['path' => 'admin/tasks', 'label' => 'Tasks', 'icon' => 'check', 'area' => 'sales'],
        ['path' => 'admin/quotes', 'label' => 'Quotes', 'icon' => 'doc', 'area' => 'sales'],
        ['path' => 'admin/invoices', 'label' => 'Invoices', 'icon' => 'receipt', 'area' => 'sales'],
        ['path' => 'admin/messages', 'label' => 'Messages', 'icon' => 'mail', 'area' => 'sales'],
        ['path' => 'admin/software-requests', 'label' => 'Software Requests', 'icon' => 'inbox', 'area' => 'sales'],
    ]],
    ['label' => 'Store & Inventory', 'items' => [
        ['path' => 'admin/inventory', 'label' => 'Inventory', 'icon' => 'pulse', 'area' => 'store'],
        ['path' => 'admin/pos', 'label' => 'Point of Sale', 'icon' => 'till', 'area' => 'store'],
        ['path' => 'admin/orders', 'label' => 'Online Orders', 'icon' => 'bag', 'area' => 'store'],
        ['path' => 'admin/products', 'label' => 'Products', 'icon' => 'box', 'area' => 'store'],
        ['path' => 'admin/purchase-orders', 'label' => 'Purchase Orders', 'icon' => 'truck', 'area' => 'store'],
        ['path' => 'admin/suppliers', 'label' => 'Suppliers', 'icon' => 'factory', 'area' => 'store'],
        ['path' => 'admin/serials', 'label' => 'Serials & Warranty', 'icon' => 'barcode', 'area' => 'store'],
        ['path' => 'admin/categories', 'label' => 'Categories', 'icon' => 'tag', 'area' => 'store'],
    ]],
    ['label' => 'Gaming', 'items' => [
        ['path' => 'admin/bookings', 'label' => 'Bookings', 'icon' => 'calendar', 'area' => 'gaming'],
        ['path' => 'admin/rooms', 'label' => 'Rooms', 'icon' => 'door', 'area' => 'gaming'],
        ['path' => 'admin/games', 'label' => 'Games', 'icon' => 'pad', 'area' => 'gaming'],
        ['path' => 'admin/equipment', 'label' => 'Hub Equipment', 'icon' => 'tool', 'area' => 'gaming'],
    ]],
    ['label' => 'Training', 'items' => [
        ['path' => 'admin/courses', 'label' => 'Courses', 'icon' => 'cap', 'area' => 'training'],
    ]],
    ['label' => 'Website', 'items' => [
        ['path' => 'admin/businesses', 'label' => 'Clients', 'icon' => 'star', 'area' => 'website'],
        ['path' => 'admin/testimonials', 'label' => 'Testimonials', 'icon' => 'quote', 'area' => 'website'],
        ['path' => 'admin/home-stats', 'label' => 'Home Stats', 'icon' => 'chart', 'area' => 'website'],
    ]],
    ['label' => 'Settings', 'items' => [
        ['path' => 'admin/staff', 'label' => 'Staff & Roles', 'icon' => 'users', 'area' => 'admin'],
        ['path' => 'admin/account', 'label' => 'My Account', 'icon' => 'user', 'area' => '*'],
    ]],
];

/** 18px line icons (stroke = currentColor). */
function admin_icon(string $name): string
{
    $paths = [
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'inbox' => '<path d="M3 13h5l1.5 3h5L16 13h5"/><path d="M5 5h14l2 8v6H3v-6z"/>',
        'bag' => '<path d="M5 8h14l-1 12H6z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/>',
        'box' => '<path d="M3 7l9-4 9 4-9 4z"/><path d="M3 7v10l9 4 9-4V7"/><path d="M12 11v10"/>',
        'tag' => '<path d="M3 12V4h8l10 10-8 8z"/><circle cx="7.5" cy="8.5" r="1.5"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'door' => '<path d="M5 21V4h11v17"/><path d="M3 21h18"/><circle cx="13" cy="12.5" r="1"/>',
        'pad' => '<path d="M7 8h10a4 4 0 0 1 4 4l.5 4a2.5 2.5 0 0 1-4.5 1.5L15 15H9l-2 2.5A2.5 2.5 0 0 1 2.5 16L3 12a4 4 0 0 1 4-4z"/><path d="M8 11v3M6.5 12.5h3"/>',
        'cap' => '<path d="M2 9l10-5 10 5-10 5z"/><path d="M6 11v5c3 2 9 2 12 0v-5"/>',
        'star' => '<path d="M12 3l2.8 5.7 6.2.9-4.5 4.4 1.1 6.2L12 17.3 6.4 20.2l1.1-6.2L3 9.6l6.2-.9z"/>',
        'quote' => '<path d="M4 18c2-1 3-3 3-6H4V6h6v6c0 4-2 6-6 6zM14 18c2-1 3-3 3-6h-3V6h6v6c0 4-2 6-6 6z"/>',
        'chart' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
        'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 14c2 .8 3.5 3 3.5 6"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'pulse' => '<path d="M3 12h4l3-8 4 16 3-8h4"/>',
        'till' => '<rect x="3" y="10" width="18" height="10" rx="2"/><path d="M7 10V4h10v6"/><path d="M7 14h2M11 14h2M15 14h2M7 17h10"/>',
        'truck' => '<path d="M2 6h12v10H2z"/><path d="M14 9h4l4 4v3h-8"/><circle cx="6" cy="18" r="2"/><circle cx="18" cy="18" r="2"/>',
        'factory' => '<path d="M3 21V10l6 4V10l6 4V5h6v16z"/><path d="M7 17h2M13 17h2"/>',
        'barcode' => '<path d="M4 5v14M7 5v14M11 5v14M14 5v14M18 5v14M20 5v14"/>',
        'tool' => '<path d="M14.5 6.5a4 4 0 0 0 5 5L12 19a2.1 2.1 0 0 1-3-3z"/><path d="M14.5 6.5L17 4l3 3-2.5 2.5"/>',
        'funnel' => '<path d="M3 4h18l-7 8v6l-4 2v-8z"/>',
        'check' => '<rect x="3" y="3" width="18" height="18" rx="3"/><path d="M8 12l3 3 5-6"/>',
        'doc' => '<path d="M6 2h9l5 5v15H6z"/><path d="M14 2v6h6M9 13h8M9 17h6"/>',
        'receipt' => '<path d="M5 2h14v20l-3-2-2 2-2-2-2 2-2-2-3 2z"/><path d="M9 7h6M9 11h6M9 15h4"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
        'menu' => '<path d="M3 6h18M3 12h18M3 18h18"/>',
        'external' => '<path d="M14 4h6v6M20 4l-9 9"/><path d="M18 14v6H4V6h6"/>',
        'logout' => '<path d="M15 4h4v16h-4"/><path d="M10 8l-4 4 4 4M6 12h11"/>',
    ];
    return '<svg class="admin-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . ($paths[$name] ?? '') . '</svg>';
}
