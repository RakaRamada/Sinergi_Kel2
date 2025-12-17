<?php
// app/controllers/LandingController.php

class LandingController {
    private $conn;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    public function index() {
        // Load view landing page
        require_once 'app/views/landing.php';
    }
}
