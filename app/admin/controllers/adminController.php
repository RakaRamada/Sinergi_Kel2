<?php

class AdminController {

    public function dashboard() {
        include __DIR__ . '../views/dashboard.php';
    }

    public function detailReport() {
        include __DIR__ . '../views/detail_report.php';
    }

    public function profile() {
        include __DIR__ . '../views/profile.php';
    }
}
