<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Role;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            ['label' => 'Dashboard', 'icon' => 'dashboard', 'route' => '/dashboard', 'sort_order' => 10],
            ['label' => 'New Booking', 'icon' => 'book', 'route' => '/booking/new', 'sort_order' => 20],
            ['label' => 'Check-in', 'icon' => 'login', 'route' => '/booking/check-in', 'sort_order' => 30],
            ['label' => 'Check-out', 'icon' => 'logout', 'route' => '/booking/check-out', 'sort_order' => 40],
            ['label' => 'Room Availability', 'icon' => 'bed', 'route' => '/booking/availability', 'sort_order' => 50],
            ['label' => 'Booking History', 'icon' => 'history', 'route' => '/booking/history', 'sort_order' => 60],
            ['label' => 'Billing', 'icon' => 'receipt', 'route' => '/billing', 'sort_order' => 70],
            ['label' => 'Housekeeping', 'icon' => 'cleaning', 'route' => '/housekeeping', 'sort_order' => 80],
            ['label' => 'Room Type Master', 'icon' => 'category', 'route' => '/masters/room-type', 'sort_order' => 100],
            ['label' => 'Room Master', 'icon' => 'door_front', 'route' => '/masters/room', 'sort_order' => 110],
            ['label' => 'Floor Master', 'icon' => 'layers', 'route' => '/masters/floor', 'sort_order' => 120],
            ['label' => 'Customer Master', 'icon' => 'people', 'route' => '/masters/customer', 'sort_order' => 130],
            ['label' => 'Staff Master', 'icon' => 'badge', 'route' => '/masters/staff', 'sort_order' => 140],
            ['label' => 'User Master', 'icon' => 'person_add', 'route' => '/masters/user', 'sort_order' => 150],
            ['label' => 'Role Master', 'icon' => 'admin_panel_settings', 'route' => '/masters/role', 'sort_order' => 152],
            ['label' => 'Menu Master', 'icon' => 'menu', 'route' => '/masters/menu', 'sort_order' => 154],
            ['label' => 'Role-Menu Mapping', 'icon' => 'link', 'route' => '/masters/role-menu', 'sort_order' => 156],
            ['label' => 'Service Master', 'icon' => 'room_service', 'route' => '/masters/service', 'sort_order' => 160],
            ['label' => 'Tax Master', 'icon' => 'percent', 'route' => '/masters/tax', 'sort_order' => 170],
            ['label' => 'Discount Master', 'icon' => 'local_offer', 'route' => '/masters/discount', 'sort_order' => 180],
            ['label' => 'Reports', 'icon' => 'assessment', 'route' => '/reports', 'sort_order' => 200],
        ];
        foreach ($menus as $m) {
            Menu::updateOrCreate(
                ['route' => $m['route']],
                ['label' => $m['label'], 'icon' => $m['icon'], 'sort_order' => $m['sort_order'], 'is_active' => true]
            );
        }
        // Assign all menus to admin
        $admin = Role::where('code', 'admin')->first();
        if ($admin) {
            $admin->menus()->sync(Menu::pluck('id'));
        }
    }
}
